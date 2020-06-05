<?php

namespace Logeecom\Infrastructure\Http;

use Logeecom\Infrastructure\Http\Exceptions\HttpRequestException;

/**
 * Class AsyncSocketHttpClient
 *
 * AsyncSocketHttpClient will send asynchronous web request by using the
 * web sockets as supported by php.
 *
 * Main purpose of the AsyncSocketHttpClient is to improve the performance
 * of the async requests especially in a multi tenant environment where
 * the major task execution bottleneck is the task starting process, since on
 * average it takes about 1 second for the curl library to actually perform the
 * request, therefore, minimal time necessary to send async request is around
 * one second or more.
 *
 * @important notice: Socket client will use curl for synchronous request. Since
 * there are no performance benefits to be had, and supporting full synchronous
 * request would imply supporting complete HTTP specification.
 *
 * @package Logeecom\Infrastructure\Http
 */
class AsyncSocketHttpClient extends CurlHttpClient
{
    /**
     * Default request timeout.
     */
    const DEFAULT_ASYNC_REQUEST_TIMEOUT = 5;
    /**
     * Sleep time after fwrite().
     */
    const FWRITE_SLEEP_USECONDS = 300000;

    /**
     * Create and send request asynchronously.
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE etc.)
     * @param string $url Request URL. Full URL where request should be sent.
     * @param array|null $headers Request headers to send. Key as header name and value as header content. Optional.
     * @param string $body Request payload. String data to send as HTTP request payload. Optional.  Default value for
     * request body is '1' to ensure minimal request data in case of POST, PUT, PATCH methods.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    protected function sendHttpRequestAsync($method, $url, $headers = array(), $body = '1')
    {
        $url = $this->adjustUrlIfNeeded($url);
        $urlDetails = parse_url($url);

        if ($urlDetails === false) {
            throw new HttpRequestException('Unable to parse request url.');
        }

        $transferProtocol = $this->getTransferProtocol($urlDetails);
        $port = $this->getTargetPort($urlDetails);
        $path = $this->getPath($urlDetails);
        $payload = $this->getRequestPayload(strtoupper($method), $urlDetails['host'], $path, $headers, $body);
        $timeOut = $this->getRequestTimeOut();

        $this->executeRequest($transferProtocol, $urlDetails['host'], $port, $timeOut, $payload);
    }

    /**
     * Deduces transfer protocol based ont the url scheme.
     *
     * @param array $urlDetails URL details formatted as the output of the parse_url method.
     *
     * @return string Returns ssl:// if scheme is HTTPS, tcp:// otherwise.
     */
    protected function getTransferProtocol(array $urlDetails)
    {
        if ($urlDetails['scheme'] === 'https') {
            return 'tls://';
        }

        return 'tcp://';
    }

    /**
     * Provides request port based on the url details.
     *
     * If the port is defined in the URL returns defined port;
     * Otherwise, if the scheme is HTTPS returns 443;
     * Otherwise, returns 80.
     *
     * @param array $urlDetails URL details formatted as the output of the parse_url method.
     *
     * @return int Request port.
     */
    protected function getTargetPort(array $urlDetails)
    {
        if (!empty($urlDetails['port'])) {
            return $urlDetails['port'];
        }

        if ($urlDetails['scheme'] === 'https') {
            return 443;
        }

        return 80;
    }

    /**
     * Retrieves request path based on url details.
     *
     * @param array $urlDetails URL details formatted as the output of the parse_url method.
     *
     * @return string Request path.
     */
    protected function getPath(array $urlDetails)
    {
        return !empty($urlDetails['path']) ? $urlDetails['path'] : '/';
    }

    /**
     * Retrieves request time out in seconds.
     *
     * @return int Request timeout in seconds.
     */
    protected function getRequestTimeOut()
    {
        $timeout = $this->getConfigService()->getAsyncRequestTimeout();

        return !empty($timeout) ? $timeout : static::DEFAULT_ASYNC_REQUEST_TIMEOUT;
    }

    /**
     * Generates request payload in accordance with the HTTP 1.1.
     *
     * @param string $method Request HTTP method.
     * @param string $host Request host.
     * @param string $path Request path.
     * @param array $headers List of request headers.
     * @param string $body Request body.
     *
     * @return string
     */
    protected function getRequestPayload($method, $host, $path, array $headers, $body)
    {
        $payload = "$method $path HTTP/1.1\r\n";
        $payload .= "Host: $host\r\n";

        foreach ($headers as $header => $value) {
            $payload .= $header . (!empty($value) ? ": $value" : '') . "\r\n";
        }

        $payload .= "Content-Length: " . strlen($body) . "\r\n";
        $payload .= "Connection: close\r\n\r\n";

        $payload .= $body . "\r\n\r\n";

        return $payload;
    }

    /**
     * Executes request by writing to the php web socket.
     *
     * @param string $transferProtocol One of 'ssl://' or 'tcp://'.
     * @param string $host Request host.
     * @param int $port Destination port.
     * @param int $timeOut Request timeout in seconds.
     * @param string $payload Payload to be written to the socket.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException Thrown when the request
     *      is not completed successfully.
     */
    protected function executeRequest($transferProtocol, $host, $port, $timeOut, $payload)
    {
        $socket = pfsockopen($transferProtocol . $host, $port, $errorCode, $errorMsg, $timeOut);
        if ($socket === false) {
            throw new HttpRequestException($errorMsg, $errorCode);
        }

        $writeResult = fwrite($socket, $payload);
        if ($writeResult === false) {
            throw new HttpRequestException('Unable to write to php socket.');
        }

        // Even though php reports that the write has been completed
        // The data is not necessarily been written
        // We must wait to make sure that the write is actually complete
        usleep(self::FWRITE_SLEEP_USECONDS);

        fclose($socket);
    }
}
