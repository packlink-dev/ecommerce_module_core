<?php

namespace Logeecom\Tests\BusinessLogic\Proxy;

use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestHttpClient;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;

/**
 * Class ProxyTest.
 *
 * @package Logeecom\Tests\BusinessLogic\Proxy
 */
class ProxyTest extends BaseTestWithServices
{
    /**
     * @var TestHttpClient
     */
    public $httpClient;

    public function setUp()
    {
        parent::setUp();

        /** @noinspection PhpUnhandledExceptionInspection */
        RepositoryRegistry::registerRepository(ShippingMethod::CLASS_NAME, MemoryRepository::getClassName());

        $this->httpClient = new TestHttpClient();
        $self = $this;

        TestServiceRegister::registerService(
            HttpClient::CLASS_NAME,
            function () use ($self) {
                return $self->httpClient;
            }
        );

        TestServiceRegister::registerService(
            Proxy::CLASS_NAME,
            function () use ($self) {
                /** @var Configuration $config */
                $config = TestServiceRegister::getService(Configuration::CLASS_NAME);

                return new Proxy($config, $self->httpClient);
            }
        );
    }

    /**
     * Tests successful response.
     */
    public function testSuccessfulResponse()
    {
        $response = file_get_contents(__DIR__ . '/../Common/ApiResponses/shipmentLabels.json');
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), $response)));

        $labels = $this->getProxy()->getLabels('asdf');

        self::assertCount(1, $labels);
    }

    /**
     * Tests the case when API returns a list of messages.
     *
     * @expectedException \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @expectedExceptionCode 400
     * @expectedExceptionMessage Error message 1
     * Error message 2.
     */
    public function testBadResponseListOfMessages()
    {
        $response = file_get_contents(__DIR__ . '/../Common/ApiResponses/badResponseMessages.json');
        $this->httpClient->setMockResponses(array(new HttpResponse(400, array(), $response)));

        $this->getProxy()->getLabels('asdf');
    }

    /**
     * Tests the case when API returns a list of messages.
     *
     * @expectedException \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @expectedExceptionCode 400
     * @expectedExceptionMessage Error message 1
     */
    public function testBadResponseMessage()
    {
        $response = '{"message": "Error message 1"}';
        $this->httpClient->setMockResponses(array(new HttpResponse(400, array(), $response)));

        $this->getProxy()->getLabels('asdf');
    }

    /**
     * Tests the case when API returns an authentication error.
     *
     * @expectedException \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @expectedExceptionCode 401
     * @expectedExceptionMessage Auth error
     */
    public function test401()
    {
        $response = '{"message": "Auth error"}';
        $this->httpClient->setMockResponses(array(new HttpResponse(401, array(), $response)));

        $this->getProxy()->getLabels('asdf');
    }

    /**
     * Tests the case when API returns a 404 error.
     */
    public function test404()
    {
        $this->httpClient->setMockResponses(array(new HttpResponse(404, array(), '')));

        self::assertEmpty($this->getProxy()->getLabels('asdf'));
    }

    public function testAsyncCall()
    {

    }

    /**
     * @return Proxy
     */
    private function getProxy()
    {
        /** @var Proxy $proxy */
        $proxy = TestServiceRegister::getService(Proxy::CLASS_NAME);

        return $proxy;
    }
}
