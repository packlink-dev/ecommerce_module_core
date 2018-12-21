<?php

namespace Logeecom\Tests\Common\TestComponents;

use Logeecom\Infrastructure\Http\DTO\OptionsDTO;
use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException;

class TestHttpClient extends HttpClient
{
    const REQUEST_TYPE_SYNCHRONOUS = 1;
    const REQUEST_TYPE_ASYNCHRONOUS = 2;
    public $calledAsync = false;
    public $additionalOptions;
    public $setAdditionalOptionsCallHistory = array();
    /**
     * @var array
     */
    private $responses;
    /**
     * @var array
     */
    private $history;

    /**
     * @inheritdoc
     */
    public function request($method, $url, $headers = array(), $body = '')
    {
        return $this->sendHttpRequest($method, $url, $headers, $body);
    }

    /**
     * @inheritdoc
     */
    public function requestAsync($method, $url, $headers = array(), $body = '')
    {
        $this->sendHttpRequestAsync($method, $url, $headers, $body);
    }

    /**
     * @inheritdoc
     */
    public function sendHttpRequest($method, $url, $headers = array(), $body = '')
    {
        $this->history[] = array(
            'type' => self::REQUEST_TYPE_SYNCHRONOUS,
            'method' => $method,
            'url' => $url,
            'headers' => $headers,
            'body' => $body,
        );

        if (empty($this->responses)) {
            throw new HttpCommunicationException('No response');
        }

        return array_shift($this->responses);
    }

    /**
     * @inheritdoc
     */
    public function sendHttpRequestAsync($method, $url, $headers = array(), $body = '')
    {
        $this->calledAsync = true;

        $this->history[] = array(
            'type' => self::REQUEST_TYPE_ASYNCHRONOUS,
            'method' => $method,
            'url' => $url,
            'headers' => $headers,
            'body' => $body,
        );
    }

    /**
     * @inheritdoc
     */
    protected function getAdditionalOptionsCombinations()
    {
        $combinations = array();
        $combinations[] = array(new OptionsDTO(CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4));
        $combinations[] = array(new OptionsDTO(CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V6));

        return $combinations;
    }

    /**
     * @inheritdoc
     */
    protected function setAdditionalOptions($options)
    {
        $this->setAdditionalOptionsCallHistory[] = $options;
        $this->additionalOptions = $options;
    }

    /**
     * @inheritdoc
     */
    protected function resetAdditionalOptions()
    {
        $this->additionalOptions = array();
    }

    /**
     * Set all mock responses.
     *
     * @param array $responses
     */
    public function setMockResponses($responses)
    {
        $this->responses = $responses;
    }

    /**
     * Return last request.
     *
     * @return array
     */
    public function getLastRequest()
    {
        return reset($this->history);
    }

    /**
     * Return call count.
     *
     * @return array
     */
    public function getHistory()
    {
        return $this->history;
    }

    /**
     * Return last request.
     *
     * @return array
     */
    public function getLastRequestHeaders()
    {
        $lastRequest = $this->getLastRequest();

        return $lastRequest['headers'];
    }
}
