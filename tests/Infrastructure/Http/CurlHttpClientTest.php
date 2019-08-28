<?php

namespace Logeecom\Tests\Infrastructure\Http;

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
    }

    /**
     * Test an async call.
     */
    public function testAsyncCall()
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
        $curlOptions = $this->httpClient->getCurlOptions();
        $this->assertNotEmpty($curlOptions, 'Curl options should be set.');
        $this->assertTrue(isset($curlOptions[CURLOPT_TIMEOUT_MS]), 'Curl timeout should be set for async call.');
        $this->assertEquals(
            CurlHttpClient::DEFAULT_ASYNC_REQUEST_TIMEOUT,
            $curlOptions[CURLOPT_TIMEOUT_MS],
            'Curl default timeout should be set for async call.'
        );
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
     * Tests 301 and 302 following redirects.
     */
    public function test30xRedirectsSuccess()
    {
        $responses = array(
            $this->getResponse(301),
            $this->getResponse(302),
            $this->getResponse(200),
        );

        $this->httpClient->setMockResponses($responses);
        $success = $this->httpClient->request('POST', 'test.url.com');

        $this->assertTrue($success->isSuccessful(), 'Sync call should pass.');
        $history = $this->httpClient->getHistory();
        $this->assertCount(1, $history);
        // original URL
        $this->assertEquals('test.url.com', $history[0]['url'], 'Base curl URL should not be changed.');
        // updated URL
        $options = $this->httpClient->getCurlOptions();
        $this->assertEquals('https://test.url.com', $options[CURLOPT_URL], 'Curl URL should be changed.');
    }

    public function test30xMaxRedirectsSuccess()
    {
        // max redirects is 5;
        $responses = array(
            $this->getResponse(301),
            $this->getResponse(302),
            $this->getResponse(302),
            $this->getResponse(301),
            $this->getResponse(302),
            $this->getResponse(200),
        );

        $this->successCall($responses);
    }

    /**
     * Tests 301 and 302 following redirects.
     */
    public function test30xRedirectsFail()
    {
        // max redirects in test client is 5;
        $responses = array(
            $this->getResponse(301),
            $this->getResponse(302),
            $this->getResponse(302),
            $this->getResponse(302),
            $this->getResponse(301),
            $this->getResponse(302),
            $this->getResponse(200),
        );

        $this->httpClient->setMockResponses($responses);
        $success = $this->httpClient->request('POST', 'test.url.com');

        $this->assertFalse($success->isSuccessful(), 'Curl call should not pass.');
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
            'data' => "HTTP/1.1 $code CUSTOM\r
Cache-Control: no-cache\r
Server: test\r
Location: https://test.url.com\r
Date: Wed Jul 4 15:32:03 2019\r
Connection: Keep-Alive:\r
Content-Type: application/json\r
Content-Length: 24860\r
X-Custom-Header: Content: database\r
\r
{\"status\":\"success\"}",
        );
    }
}
