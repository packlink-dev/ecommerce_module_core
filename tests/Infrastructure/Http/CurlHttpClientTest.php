<?php

namespace Logeecom\Tests\Infrastructure\Http;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\Http\CurlHttpClient;
use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Tests\Infrastructure\Common\BaseInfrastructureTestWithServices;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestCurlHttpClient;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;

class CurlHttpClientTest extends BaseInfrastructureTestWithServices
{
    /**
     * @var TestCurlHttpClient
     */
    protected $httpClient;

    protected function setUp()
    {
        parent::setUp();

        $this->httpClient = new TestCurlHttpClient();
        $me = $this;
        TestServiceRegister::registerService(
            HttpClient::CLASS_NAME,
            function () use ($me) {
                return $me->httpClient;
            }
        );
    }

    /**
     * Test a sync call.
     */
    public function testSyncCall()
    {
        $responses = array($this->getResponse(200));

        $this->successCall($responses);
        $this->assertCallTimeout(CurlHttpClient::DEFAULT_REQUEST_TIMEOUT);
    }

    /**
     * Test a sync call.
     */
    public function testSyncCallWithDifferentTimeout()
    {
        $newTimeout = 20000;
        $this->shopConfig->setSyncRequestTimeout($newTimeout);

        $responses = array($this->getResponse(200));

        $this->successCall($responses);
        $this->assertCallTimeout($newTimeout);
    }

    /**
     * Test an async call.
     */
    public function testDefaultAsyncCall()
    {
        $responses = array($this->getResponse(200));

        $this->httpClient->setMockResponses($responses);

        $this->httpClient->requestAsync('POST', 'test.url.com');

        $history = $this->httpClient->getHistory();
        $this->assertCount(1, $history);
        $this->assertEquals(
            TestCurlHttpClient::REQUEST_TYPE_ASYNCHRONOUS,
            $history[0]['type'],
            'Async call should pass.'
        );
        $this->assertCallTimeout(CurlHttpClient::DEFAULT_ASYNC_REQUEST_TIMEOUT);
    }

    /**
     * Test an async call with custom timeout.
     */
    public function testDefaultAsyncCallDifferentTimeout()
    {
        $responses = array($this->getResponse(200));

        $this->httpClient->setMockResponses($responses);

        $newTimeout = 200;
        $this->shopConfig->setAsyncRequestTimeout($newTimeout);
        $this->httpClient->requestAsync('POST', 'test.url.com');

        $this->assertCallTimeout($newTimeout);
    }

    /**
     * Test async call with progress callback
     */
    public function testAsyncCallWithProgressCallback()
    {
        $responses = array($this->getResponse(200));
        $this->httpClient->setMockResponses($responses);

        $this->shopConfig->setAsyncRequestWithProgress(true);
        $this->httpClient->requestAsync('POST', 'test.url.com');

        $this->assertProgressCallback();
        $this->assertCallTimeout(Configuration::DEFAULT_ASYNC_REQUEST_WITH_PROGRESS_TIMEOUT);
    }

    /**
     * Test async call without progress callback
     */
    public function testAsyncCallWithoutProgressCallback()
    {
        $responses = array($this->getResponse(200));

        $this->httpClient->setMockResponses($responses);

        $this->shopConfig->setAsyncRequestWithProgress(false);
        $this->httpClient->requestAsync('POST', 'test.url.com');

        $this->assertProgressCallback(false);
        $this->assertCallTimeout(CurlHttpClient::DEFAULT_ASYNC_REQUEST_TIMEOUT);
    }

    /**
     * Test an async call with custom timeout and progress callback.
     */
    public function testDAsyncCallWithProgressCallbackDifferentTimeout()
    {
        $responses = array($this->getResponse(200));

        $this->httpClient->setMockResponses($responses);

        $newTimeout = 200;
        $this->shopConfig->setAsyncRequestWithProgress(true);
        $this->shopConfig->setAsyncRequestTimeout($newTimeout);
        $this->httpClient->requestAsync('POST', 'test.url.com');

        $this->assertCallTimeout($newTimeout);
    }

    /**
     * Test parsing plain text response.
     */
    public function testParsingResponse()
    {
        $response = $this->successCall(array($this->getResponse(200)));

        $this->assertEquals(200, $response->getStatus());
        $headers = $response->getHeaders();
        $this->assertCount(9, $headers);
        $this->assertEquals('HTTP/1.1 200 CUSTOM', $headers[0]);

        $this->assertTrue(array_key_exists('Cache-Control', $headers));
        $this->assertEquals('no-cache', $headers['Cache-Control']);
        $this->assertTrue(array_key_exists('Server', $headers));
        $this->assertEquals('test', $headers['Server']);
        $this->assertTrue(array_key_exists('Date', $headers));
        $this->assertEquals('Wed Jul 4 15:32:03 2019', $headers['Date']);
        $this->assertTrue(array_key_exists('Connection', $headers));
        $this->assertEquals('Keep-Alive:', $headers['Connection']);
        $this->assertTrue(array_key_exists('Content-Type', $headers));
        $this->assertEquals('application/json', $headers['Content-Type']);
        $this->assertTrue(array_key_exists('Content-Length', $headers));
        $this->assertEquals('24860', $headers['Content-Length']);
        $this->assertTrue(array_key_exists('X-Custom-Header', $headers));
        $this->assertEquals('Content: database', $headers['X-Custom-Header']);

        $body = json_decode($response->getBody(), true);
        $this->assertCount(1, $body);
        $this->assertTrue(array_key_exists('status', $body));
        $this->assertEquals('success', $body['status']);
    }

    /**
     * Tests setting follow location.
     */
    public function testFollowLocation()
    {
        $this->httpClient->setFollowLocation(false);
        $this->successCall(array($this->getResponse(200)));

        $options = $this->httpClient->getCurlOptions();
        $this->assertArrayNotHasKey(CURLOPT_FOLLOWLOCATION, $options, 'Curl FOLLOWLOCATION should be set.');
    }

    private function successCall($responses)
    {
        $this->httpClient->setMockResponses($responses);
        $success = $this->httpClient->request('POST', 'test.url.com');

        $this->assertTrue($success->isSuccessful(), 'Sync call should pass.');
        $this->assertCount(1, $this->httpClient->getHistory());
        $this->assertNotEmpty($this->httpClient->getCurlOptions(), 'Curl options should be set.');

        return $success;
    }

    private function getResponse($code)
    {
        return array(
            'status' => $code,
            'headers' => array(
                "HTTP/1.1 $code CUSTOM",
                'Cache-Control' => 'no-cache',
                'Server' => 'test',
                'Location' => 'https://test.url.com',
                'Date' => 'Wed Jul 4 15:32:03 2019',
                'Connection' => 'Keep-Alive:',
                'Content-Type' => 'application/json',
                'Content-Length' => '24860',
                'X-Custom-Header' => 'Content: database',
            ),
            'data' => "{\"status\":\"success\"}",
        );
    }

    /**
     * @param $timeout
     */
    private function assertCallTimeout($timeout)
    {
        $curlOptions = $this->httpClient->getCurlOptions();
        $this->assertNotEmpty($curlOptions, 'Curl options should be set.');
        $this->assertTrue(isset($curlOptions[CURLOPT_TIMEOUT_MS]), 'Curl timeout should be set for async call.');
        $this->assertEquals(
            $timeout,
            $curlOptions[CURLOPT_TIMEOUT_MS],
            'Curl default timeout should be set for async call.'
        );
    }

    private function assertProgressCallback($isOn = true)
    {
        $curlOptions = $this->httpClient->getCurlOptions();
        $this->assertNotEmpty($curlOptions, 'Curl options should be set.');
        if (!$isOn) {
            $this->assertFalse(
                isset($curlOptions[CURLOPT_NOPROGRESS]),
                'Curl progress callback should not be set.'
            );
            $this->assertFalse(
                isset($curlOptions[CURLOPT_PROGRESSFUNCTION]),
                'Curl progress callback should not be set.'
            );

            return;
        }


        $this->assertTrue(
            isset($curlOptions[CURLOPT_NOPROGRESS]),
            'Curl progress callback should be set for async call.'
        );
        $this->assertFalse(
            $curlOptions[CURLOPT_NOPROGRESS],
            'Curl progress callback should be set for async call.'
        );
        $this->assertTrue(
            isset($curlOptions[CURLOPT_PROGRESSFUNCTION]),
            'Curl progress callback should be set for async call.'
        );
        $this->assertEquals(
            array($this->httpClient, 'abortAfterAsyncRequestCallback'),
            $curlOptions[CURLOPT_PROGRESSFUNCTION],
            'Curl progress callback should be set for async call.'
        );
    }
}
