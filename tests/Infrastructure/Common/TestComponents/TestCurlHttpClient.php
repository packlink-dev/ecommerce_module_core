<?php
/** @noinspection PhpMissingDocCommentInspection */

namespace Logeecom\Tests\Infrastructure\Common\TestComponents;

use Logeecom\Infrastructure\Http\CurlHttpClient;
use Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException;

class TestCurlHttpClient extends CurlHttpClient
{
    const REQUEST_TYPE_SYNCHRONOUS = 1;
    const REQUEST_TYPE_ASYNCHRONOUS = 2;
    const MAX_REDIRECTS = 5;
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
     * Set all mock responses.
     *
     * @param array $responses
     */
    public function setMockResponses($responses)
    {
        $this->responses = $responses;
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
     * Gets cURL options set for the request.
     *
     * @return array Curl options.
     */
    public function getCurlOptions()
    {
        return $this->curlOptions;
    }

    /**
     * Sets indicator whether to follow location or not.
     *
     * @param bool $follow
     */
    public function setFollowLocation($follow)
    {
        $this->followLocation = $follow;
    }

    /**
     * @inheritdoc
     */
    protected function executeSynchronousRequest()
    {
        $this->setHistory(self::REQUEST_TYPE_SYNCHRONOUS);

        return parent::executeSynchronousRequest();
    }

    /**
     * @inheritdoc
     */
    protected function executeAsynchronousRequest()
    {
        $this->setHistory(self::REQUEST_TYPE_ASYNCHRONOUS);

        return parent::executeAsynchronousRequest();
    }

    /**
     * Mocks cURL request and returns response and status code.
     *
     * @return array Array with plain response as the first item and status code as the second item.
     */
    protected function executeCurlRequest()
    {
        if (empty($this->responses)) {
            throw new HttpCommunicationException('No response');
        }

        $response = array_shift($this->responses);

        return array($response['data'], $response['status']);
    }

    /**
     * @inheritdoc
     */
    protected function setAdditionalOptions($domain, $options)
    {
        parent::setAdditionalOptions($domain, $options);
        $this->setAdditionalOptionsCallHistory[$domain][] = $options;
    }

    /**
     * Sets call history.
     *
     * @param int $type
     */
    protected function setHistory($type)
    {
        $this->history[] = array(
            'type' => $type,
            'method' => isset($this->curlOptions[CURLOPT_CUSTOMREQUEST]) ? $this->curlOptions[CURLOPT_CUSTOMREQUEST]
                : 'POST',
            'url' => $this->curlOptions[CURLOPT_URL],
            'headers' => $this->curlOptions[CURLOPT_HTTPHEADER],
            'body' => isset($this->curlOptions[CURLOPT_POSTFIELDS]) ? $this->curlOptions[CURLOPT_POSTFIELDS] : '',
        );
    }
}
