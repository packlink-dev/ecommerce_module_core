<?php
/** @noinspection PhpUnused */

/** @noinspection PhpMissingDocCommentInspection */

namespace Logeecom\Tests\Infrastructure\Common\TestComponents;

use Logeecom\Infrastructure\Http\DTO\OptionsDTO;
use Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Logeecom\Infrastructure\Http\HttpClient;

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
     * @var array
     */
    private $autoConfigurationCombinations = array();

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
    protected function getAutoConfigurationOptionsCombinations($method, $url)
    {
        if (empty($this->autoConfigurationCombinations)) {
            $this->setAdditionalOptionsCombinations(
                array(
                    array(new OptionsDTO(CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4)),
                    array(new OptionsDTO(CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V6)),
                )
            );
        }

        return $this->autoConfigurationCombinations;
    }

    /**
     * Sets the additional HTTP options combinations.
     *
     * @param array $combinations
     */
    protected function setAdditionalOptionsCombinations(array $combinations)
    {
        $this->autoConfigurationCombinations = $combinations;
    }

    /**
     * Save additional options for request.
     *
     * @param string $domain A domain for which to reset configuration options.
     * @param OptionsDTO[] $options Additional option to add to HTTP request.
     */
    protected function setAdditionalOptions($domain, $options)
    {
        $this->setAdditionalOptionsCallHistory[] = $options;
        $this->additionalOptions = $options;
    }

    /**
     * Reset additional options for request to default value.
     *
     * @param string $domain A domain for which to reset configuration options.
     */
    protected function resetAdditionalOptions($domain)
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
        return end($this->history);
    }

    /**
     * Return call history.
     *
     * @return array
     */
    public function getHistory()
    {
        return $this->history;
    }

    /**
     * Resets the history call stack.
     */
    public function resetHistory()
    {
        $this->history = null;
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
