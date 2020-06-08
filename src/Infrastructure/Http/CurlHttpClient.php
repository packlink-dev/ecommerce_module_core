<?php

namespace Logeecom\Infrastructure\Http;

use Logeecom\Infrastructure\Http\DTO\Options;
use Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Logeecom\Infrastructure\Logger\Logger;

/**
 * Class CurlHttpClientService. In charge of doing a HTTP request by using cURL library.
 *
 * @package Logeecom\Infrastructure\Http
 */
class CurlHttpClient extends HttpClient
{
    /**
     * Default asynchronous request timeout value in milliseconds.
     */
    const DEFAULT_ASYNC_REQUEST_TIMEOUT = 1000;
    /**
     * Default asynchronous request timeout value in milliseconds when progress callback is used.
     */
    const DEFAULT_ASYNC_REQUEST_WITH_PROGRESS_TIMEOUT = 60000;
    /**
     * Default synchronous request timeout value in milliseconds.
     */
    const DEFAULT_REQUEST_TIMEOUT = 60000;
    /**
     * Maximum number of 30x response redirects.
     */
    const MAX_REDIRECTS = 10;
    /**
     * Config option that indicates whether to switch HTTP and HTTPS protocol.
     */
    const SWITCH_PROTOCOL = 'SWITCH_PROTOCOL';
    /**
     * Indicates whether to use SSL verification.
     */
    const SSL_STRICT_MODE = false;
    /**
     * cURL options for the request.
     *
     * @var array
     */
    protected $curlOptions;
    /**
     * Indicates whether to let cURL follow location.
     *
     * @var bool
     */
    protected $followLocation = true;
    /**
     * cURL handler.
     *
     * @var resource
     */
    private $curlSession;

    /**
     * Create and send request.
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE etc.)
     * @param string $url Request URL. Full URL where request should be sent.
     * @param array|null $headers Request headers to send. Key as header name and value as header content. Optional.
     * @param string $body Request payload. String data to send as HTTP request payload. Optional.
     *
     * @return HttpResponse Response object.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     *      Only in situation when there is no connection or no response.
     */
    protected function sendHttpRequest($method, $url, $headers = array(), $body = '')
    {
        $this->setCurlFollowLocationFlag();
        $this->setCurlSessionAndCommonRequestParts($method, $url, $headers, $body);
        $this->setCurlSessionOptionsForSynchronousRequest();
        $this->setCurlOptions();

        return $this->executeSynchronousRequest();
    }

    /**
     * Create and send request asynchronously.
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE etc.)
     * @param string $url Request URL. Full URL where request should be sent.
     * @param array|null $headers [Optional] Request headers to send. Key as header name and value as header content.
     * @param string $body [Optional] Request payload. String data to send as HTTP request payload. Default value for
     *     request body is '1' to ensure minimal request data in case of POST, PUT, PATCH methods. This will ensure
     *     that we have the upload progress and enable the async request termination as soon as the upload is finished
     *     without waiting for a response (without downloading a body or relaying on a fixed request timeout).
     *
     * @return bool|string
     */
    protected function sendHttpRequestAsync($method, $url, $headers = array(), $body = '1')
    {
        $this->setCurlSessionAndCommonRequestParts($method, $url, $headers, $body);
        $this->setCurlSessionOptionsForAsynchronousRequest();
        $this->setCurlOptions();

        return $this->executeAsynchronousRequest();
    }

    /**
     * Executes and returns response for synchronous request.
     *
     * @return HttpResponse A response object.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     */
    protected function executeSynchronousRequest()
    {
        list($result, $statusCode, $headers) = $this->executeCurlRequest();

        if ($result === false) {
            $error = curl_errno($this->curlSession) . ' = ' . curl_error($this->curlSession);
            curl_close($this->curlSession);

            throw new HttpCommunicationException(
                'Request ' . $this->curlOptions[CURLOPT_URL] . ' failed. ERROR: ' . $error
            );
        }

        curl_close($this->curlSession);

        return new HttpResponse($statusCode, $headers, $result);
    }

    /**
     * Executes asynchronous request.
     *
     * @return string Request result if the request finished before the timeout.
     */
    protected function executeAsynchronousRequest()
    {
        list($result, $statusCode) = $this->executeCurlRequest();

        // 0 status code is set when timeout is reached
        if (!in_array($statusCode, array(0, 200), true)) {
            $curlError = '';
            if (curl_errno($this->curlSession)) {
                $curlError = ' cURL error: ' . curl_errno($this->curlSession) . ' > ' . curl_error($this->curlSession);
            }

            $httpError = $statusCode . ' Message: ' . $result . $curlError;
            Logger::logError('Async process failed. ERROR: ' . $httpError);
        }

        curl_close($this->curlSession);

        return $result;
    }

    /**
     * Sets cURL session and common request parts.
     *
     * @param string $method Request method.
     * @param string $url Request URL.
     * @param array $headers Array of request headers.
     * @param string $body Request body.
     */
    protected function setCurlSessionAndCommonRequestParts($method, $url, array $headers, $body)
    {
        $this->initializeCurlSession();
        $this->setCurlSessionOptionsBasedOnMethod($method);
        $this->setCurlSessionUrlHeadersAndBody($method, $url, $headers, $body);
        $this->setCommonOptionsForCurlSession();
    }

    /**
     * Initializes cURL session.
     */
    protected function initializeCurlSession()
    {
        // this constant is not defined prior to php 7.0.7
        if (!defined('CURL_REDIR_POST_ALL')) {
            define('CURL_REDIR_POST_ALL', 7);
        }

        $this->curlSession = curl_init();
        $this->curlOptions = array();
    }

    /**
     * Sets cURL session option based on request method.
     *
     * @param string $method Request method.
     */
    protected function setCurlSessionOptionsBasedOnMethod($method)
    {
        if ($method === static::HTTP_METHOD_POST) {
            // follow 30x redirects with POST
            $this->curlOptions[CURLOPT_POSTREDIR] = CURL_REDIR_POST_ALL;
        }

        $this->curlOptions[CURLOPT_CUSTOMREQUEST] = $method;
    }

    /**
     * Sets cURL session URL, headers, and request body.
     *
     * @param string $method Request method.
     * @param string $url Request URL.
     * @param array $headers Array of request headers.
     * @param string $body Request body.
     */
    protected function setCurlSessionUrlHeadersAndBody($method, $url, array $headers, $body)
    {
        $this->curlOptions[CURLOPT_URL] = $this->adjustUrlIfNeeded($url);
        $this->curlOptions[CURLOPT_HTTPHEADER] = $headers;

        $methodsWithBody = array(static::HTTP_METHOD_POST, static::HTTP_METHOD_PUT, static::HTTP_METHOD_PATCH);
        if (in_array($method, $methodsWithBody, true)) {
            $this->curlOptions[CURLOPT_POSTFIELDS] = $body;
        }
    }

    /**
     * Sets common options for cURL session.
     * @noinspection CurlSslServerSpoofingInspection
     */
    protected function setCommonOptionsForCurlSession()
    {
        $this->curlOptions[CURLOPT_RETURNTRANSFER] = true;
        if ($this->followLocation) {
            $this->curlOptions[CURLOPT_FOLLOWLOCATION] = true;

            // stop possible endless redirect loop when following 30x redirects.
            $this->curlOptions[CURLOPT_MAXREDIRS] = static::MAX_REDIRECTS;
        }

        $this->curlOptions[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
        $this->curlOptions[CURLOPT_SSL_VERIFYPEER] = static::SSL_STRICT_MODE;
        $this->curlOptions[CURLOPT_SSL_VERIFYHOST] = static::SSL_STRICT_MODE;
        // Set default user agent, because for some shops if user agent is missing, request will not work.
        $this->curlOptions[CURLOPT_USERAGENT] =
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.186 Safari/537.36';
    }

    /**
     * Sets cURL session options for synchronous request.
     */
    protected function setCurlSessionOptionsForSynchronousRequest()
    {
        $this->curlOptions[CURLOPT_TIMEOUT_MS] =
            $this->getConfigService()->getSyncRequestTimeout() ?: static::DEFAULT_REQUEST_TIMEOUT;
    }

    /**
     * Sets cURL session options for asynchronous request.
     */
    protected function setCurlSessionOptionsForAsynchronousRequest()
    {
        // Always ensure the connection is fresh.
        $this->curlOptions[CURLOPT_FRESH_CONNECT] = true;
        // Timeout super fast once connected, so it goes into async.
        $asyncRequestTimeout = $this->getConfigService()->getAsyncRequestTimeout();
        $this->curlOptions[CURLOPT_TIMEOUT_MS] = $asyncRequestTimeout ?: static::DEFAULT_ASYNC_REQUEST_TIMEOUT;
    }

    /**
     * Sets a call options to the cURL instance.
     */
    protected function setCurlOptions()
    {
        $this->setCurlSessionOptionsFromConfiguration();
        curl_setopt_array($this->curlSession, $this->curlOptions);
    }

    /**
     * If some configuration options were set in the configuration, use them.
     * This is usually done if the auto-configuration is used.
     */
    protected function setCurlSessionOptionsFromConfiguration()
    {
        $domain = parse_url($this->curlOptions[CURLOPT_URL], PHP_URL_HOST);
        $options = $this->getAdditionalOptions($domain);
        foreach ($options as $key => $value) {
            if ($key !== static::SWITCH_PROTOCOL) {
                $this->curlOptions[$key] = $value;
            }
        }
    }

    /**
     * Executes cURL request and returns response and status code.
     *
     * @return array Array with plain response as the first item, status code as the second item and headers as third.
     * @noinspection PhpUnusedParameterInspection
     */
    protected function executeCurlRequest()
    {
        $headers = array();
        curl_setopt(
            $this->curlSession,
            CURLOPT_HEADERFUNCTION,
            // Callback function is called by curl for each header line received
            function ($curl, $header) use (&$headers) {
                // Set only valid headers
                $headerArray = explode(':', $header, 2);
                if (count($headerArray) >= 2) {
                    $headers[trim($headerArray[0])] = trim($headerArray[1]);
                }

                // Do not use mb_strlen here because curl expects number of bytes to be returned not number of chars
                return strlen($header);
            }
        );

        return array(curl_exec($this->curlSession), curl_getinfo($this->curlSession, CURLINFO_HTTP_CODE), $headers);
    }

    /**
     * Get additional options combinations for request.
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE etc.)
     * @param string $url Request URL. Full URL where request should be sent.
     *
     * @return array
     *  Array of additional options combinations. Each array item should be an array of Options instances.
     */
    protected function getAutoConfigurationOptionsCombinations($method, $url)
    {
        /**
         * Combinations to use:
         * CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V6 (default is CURL_IPRESOLVE_V4)
         * CURLOPT_FOLLOWLOCATION => false (default is true)
         * SWITCH_PROTOCOL => This is not a cURL option and is treated differently. Default is false.
         */
        $switchProtocol = new Options(static::SWITCH_PROTOCOL, true);
        $ipVersion = new Options(CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V6);
        if ($this->followLocation) {
            $followLocation = new Options(CURLOPT_FOLLOWLOCATION, false);

            return array(
                array($switchProtocol),
                array($followLocation),
                array($switchProtocol, $followLocation),
                array($ipVersion),
                array($switchProtocol, $ipVersion),
                array($followLocation, $ipVersion),
                array($switchProtocol, $followLocation, $ipVersion),
            );
        }

        return array(
            array($switchProtocol),
            array($ipVersion),
            array($switchProtocol, $ipVersion),
        );
    }

    /**
     * Changes between http and https protocol if needed.
     *
     * @param string $url URL to update.
     *
     * @return string Updated URL.
     */
    protected function adjustUrlIfNeeded($url)
    {
        $domain = parse_url($url, PHP_URL_HOST);
        $options = $this->getAdditionalOptions($domain);
        if (!empty($options[static::SWITCH_PROTOCOL])) {
            if (mb_strpos($url, 'http:') === 0) {
                $url = str_replace('http:', 'https:', $url);
            } else {
                $url = str_replace('https:', 'http:', $url);
            }
        }

        return $url;
    }

    /**
     * Determines whether to let cURL follow location based on the environment settings.
     */
    protected function setCurlFollowLocationFlag()
    {
        // when 'open_basedir' is set, some servers will return curl error upon initializing curl options
        // when setting curl option to follow location
        if (ini_get('open_basedir')) {
            $this->followLocation = false;
        }
    }
}
