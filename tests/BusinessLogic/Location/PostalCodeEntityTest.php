<?php

namespace Logeecom\Tests\BusinessLogic\Location;

use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestHttpClient;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Http\DTO\PostalCode;
use Packlink\BusinessLogic\Http\Proxy;

class PostalCodeEntityTest extends BaseTestWithServices
{
    /**
     * @var \Logeecom\Tests\Infrastructure\Common\TestComponents\TestHttpClient
     */
    public $httpClient;

    protected function setUp()
    {
        parent::setUp();

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

    public function testRetrievingPostalCodes()
    {
        $this->httpClient->setMockResponses($this->getSuccessfulResponses());
        /** @var Proxy $proxy */
        $proxy = TestServiceRegister::getService(Proxy::CLASS_NAME);

        /** @noinspection PhpUnhandledExceptionInspection */
        $postalCodes = $proxy->getPostalCodes('ES', '28041');

        self::assertCount(1, $postalCodes);

        $postalCode = $postalCodes[0];
        self::assertEquals('28041', $postalCode->zipcode);
        self::assertEquals('Madrid', $postalCode->city);
        self::assertEquals('Comunidad de Madrid', $postalCode->state);
        self::assertEquals('Madrid', $postalCode->province);
        self::assertEquals('España', $postalCode->country);
    }

    /**
     * @expectedException \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @expectedExceptionCode 404
     * @expectedExceptionMessage 404 Not found.
     */
    public function testFailedPostalCodesRetrieval()
    {
        $this->httpClient->setMockResponses($this->getFailedResponses());
        /** @var Proxy $proxy */
        $proxy = TestServiceRegister::getService(Proxy::CLASS_NAME);

        /** @noinspection PhpUnhandledExceptionInspection */
        $proxy->getPostalCodes('ES', '28041');
    }

    public function testCreatingPostalCodeFromArray()
    {
        $this->httpClient->setMockResponses($this->getSuccessfulResponses());
        /** @var Proxy $proxy */
        $proxy = TestServiceRegister::getService(Proxy::CLASS_NAME);

        /** @noinspection PhpUnhandledExceptionInspection */
        $postalCodes = $proxy->getPostalCodes('ES', '28041');

        $model = $postalCodes[0];
        $copy = PostalCode::fromArray($model->toArray());

        self::assertEquals($model->zipcode, $copy->zipcode);
        self::assertEquals($model->city, $copy->city);
        self::assertEquals($model->state, $copy->state);
        self::assertEquals($model->province, $copy->province);
        self::assertEquals($model->country, $copy->country);
    }

    /**
     * Retrieves successful response.
     *
     * @return HttpResponse[]
     */
    protected function getSuccessfulResponses()
    {
        $response = file_get_contents(__DIR__ . '/../Common/ApiResponses/postalCodes.json');

        return array(new HttpResponse(200, array(), $response));
    }

    /**
     * Retrieves unsuccessful response.
     *
     * @return HttpResponse[]
     */
    protected function getFailedResponses()
    {
        return array(new HttpResponse(404, array(), '[]'));
    }
}
