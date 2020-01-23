<?php

namespace Logeecom\Tests\BusinessLogic\Proxy;

use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;

/**
 * Class ProxyTest.
 *
 * @package Logeecom\Tests\BusinessLogic\Proxy
 */
class ProxyTest extends BaseTestWithServices
{
    public function setUp()
    {
        parent::setUp();

        /** @noinspection PhpUnhandledExceptionInspection */
        RepositoryRegistry::registerRepository(ShippingMethod::CLASS_NAME, MemoryRepository::getClassName());
    }

    /**
     * Tests successful response.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
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
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
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
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
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
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function test401()
    {
        $response = '{"message": "Auth error"}';
        $this->httpClient->setMockResponses(array(new HttpResponse(401, array(), $response)));

        $this->getProxy()->getLabels('asdf');
    }

    /**
     * Tests the case when API returns a 404 error.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function test404()
    {
        $this->httpClient->setMockResponses(array(new HttpResponse(404, array(), '')));

        self::assertEmpty($this->getProxy()->getLabels('asdf'));
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
