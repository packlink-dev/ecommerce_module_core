<?php

namespace Logeecom\Tests\Infrastructure\Http;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestAsyncSocketHttpClient;
use Logeecom\Tests\Infrastructure\Common\BaseInfrastructureTestWithServices;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;

class AsyncSocketHttpClientTest extends BaseInfrastructureTestWithServices
{
    public $client;

    protected function setUp()
    {
        parent::setUp();

        $this->client = new TestAsyncSocketHttpClient();
    }

    public function testTransferProtocolHttps()
    {
        // arrange
        $url = 'https://google.com';

        // act
        $this->client->requestAsync('GET', $url);

        // assert
        $this->assertEquals('tls://', $this->client->requestHistory[0]['transferProtocol']);
    }

    public function testTransferProtocolHttp()
    {
        // arrange
        $url = 'http://google.com';

        // act
        $this->client->requestAsync('GET', $url);

        // assert
        $this->assertEquals('tcp://', $this->client->requestHistory[0]['transferProtocol']);
    }

    public function testHost()
    {
        // arrange
        $url = 'http://user:password@google.com/some/path?query=test#fragment';

        // act
        $this->client->requestAsync('GET', $url);

        // assert
        $this->assertEquals('google.com', $this->client->requestHistory[0]['host']);
    }

    public function testDefaultHttpsPort()
    {
        // arrange
        $url = 'https://user:password@google.com/some/path?query=test#fragment';

        // act
        $this->client->requestAsync('GET', $url);

        // assert
        $this->assertEquals(443, $this->client->requestHistory[0]['port']);
    }

    public function testDefaultHttpPort()
    {
        // arrange
        $url = 'http://user:password@google.com/some/path?query=test#fragment';

        // act
        $this->client->requestAsync('GET', $url);

        // assert
        $this->assertEquals(80, $this->client->requestHistory[0]['port']);
    }

    public function testCustomHttpPort()
    {
        // arrange
        $url = 'http://user:password@google.com:1234/some/path?query=test#fragment';

        // act
        $this->client->requestAsync('GET', $url);

        // assert
        $this->assertEquals(1234, $this->client->requestHistory[0]['port']);
    }

    public function testDefaultTimeout()
    {
        // arrange
        $url = 'http://user:password@google.com/some/path?query=test#fragment';

        // act
        $this->client->requestAsync('GET', $url);

        // assert
        $this->assertEquals(5, $this->client->requestHistory[0]['timeout']);
    }

    public function testCustomTimeout()
    {
        // arrange
        $url = 'http://user:password@google.com/some/path?query=test#fragment';
        /** @var Configuration $configService */
        $configService = TestServiceRegister::getService(Configuration::CLASS_NAME);
        $configService->setAsyncRequestTimeout(10);

        // act
        $this->client->requestAsync('GET', $url);

        // assert
        $this->assertEquals(10, $this->client->requestHistory[0]['timeout']);
    }
}