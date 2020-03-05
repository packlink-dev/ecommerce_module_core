<?php

namespace Logeecom\Tests\BusinessLogic\Location;

use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Http\DTO\PostalZone;
use Packlink\BusinessLogic\Http\Proxy;

/**
 * Class PostalZoneEntityTest
 *
 * @package BusinessLogic\Location
 */
class PostalZoneEntityTest extends BaseTestWithServices
{
    public function testRetrievingPostalZones()
    {
        $this->httpClient->setMockResponses($this->getSuccessfulResponses());
        /** @var Proxy $proxy */
        $proxy = TestServiceRegister::getService(Proxy::CLASS_NAME);

        /** @noinspection PhpUnhandledExceptionInspection */
        $postalZones = $proxy->getPostalZones('DE');

        self::assertCount(2, $postalZones);

        self::assertEquals('3', $postalZones[0]->id);
        self::assertEquals('DE', $postalZones[0]->isoCode);
        self::assertEquals(true, $postalZones[0]->hasPostalCodes);
        self::assertEquals('Germany', $postalZones[0]->name);
        self::assertEquals('+49', $postalZones[0]->phonePrefix);

        self::assertEquals('248', $postalZones[1]->id);
        self::assertEquals('DE', $postalZones[1]->isoCode);
        self::assertEquals(true, $postalZones[1]->hasPostalCodes);
        self::assertEquals('Germany - Helgoland', $postalZones[1]->name);
        self::assertEquals('+49', $postalZones[1]->phonePrefix);
    }

    /**
     * @expectedException \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @expectedExceptionCode 404
     * @expectedExceptionMessage 404 Not found.
     */
    public function testFailedPostalZonesRetrieval()
    {
        $this->httpClient->setMockResponses($this->getFailedResponses());
        /** @var Proxy $proxy */
        $proxy = TestServiceRegister::getService(Proxy::CLASS_NAME);

        /** @noinspection PhpUnhandledExceptionInspection */
        $proxy->getPostalZones('ES');
    }

    public function testCreatingPostalZoneFromArray()
    {
        $this->httpClient->setMockResponses($this->getSuccessfulResponses());
        /** @var Proxy $proxy */
        $proxy = TestServiceRegister::getService(Proxy::CLASS_NAME);

        /** @noinspection PhpUnhandledExceptionInspection */
        $postalZones = $proxy->getPostalZones('DE');

        $model = $postalZones[0];
        $copy = PostalZone::fromArray($model->toArray());

        self::assertEquals($model->id, $copy->id);
        self::assertEquals($model->name, $copy->name);
        self::assertEquals($model->isoCode, $copy->isoCode);
        self::assertEquals($model->phonePrefix, $copy->phonePrefix);
        self::assertEquals($model->hasPostalCodes, $copy->hasPostalCodes);
    }

    /**
     * Retrieves successful response.
     *
     * @return HttpResponse[]
     */
    protected function getSuccessfulResponses()
    {
        $response = file_get_contents(__DIR__ . '/../Common/ApiResponses/postalZones.json');

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
