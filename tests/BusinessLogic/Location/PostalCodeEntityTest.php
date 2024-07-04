<?php

namespace Logeecom\Tests\BusinessLogic\Location;

use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Http\DTO\PostalCode;
use Packlink\BusinessLogic\Http\Proxy;

/**
 * Class PostalCodeEntityTest.
 *
 * @package Logeecom\Tests\BusinessLogic\Location
 */
class PostalCodeEntityTest extends BaseTestWithServices
{
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
     * @return void
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     */
    public function testFailedPostalCodesRetrieval()
    {
        $this->httpClient->setMockResponses($this->getFailedResponses());
        /** @var Proxy $proxy */
        $proxy = TestServiceRegister::getService(Proxy::CLASS_NAME);

        $exThrown = null;
        try {
            /** @noinspection PhpUnhandledExceptionInspection */
            $proxy->getPostalCodes('ES', '28041');
        } catch (\Logeecom\Infrastructure\Http\Exceptions\HttpRequestException $ex) {
            $exThrown = $ex;
        }

        $this->assertNotNull($exThrown);
        $this->assertEquals(404, $exThrown->getCode());
        $this->assertEquals('404 Not found.',  $exThrown->getMessage());
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
