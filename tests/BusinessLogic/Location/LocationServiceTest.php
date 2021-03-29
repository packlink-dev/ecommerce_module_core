<?php

namespace Logeecom\Tests\BusinessLogic\Location;

use InvalidArgumentException;
use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\TestWarehouse;
use Logeecom\Tests\BusinessLogic\ShippingMethod\TestShopShippingMethodService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Http\DTO\ShippingServiceDetails;
use Packlink\BusinessLogic\Location\LocationService;
use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\ShippingMethod\PackageTransformer;
use Packlink\BusinessLogic\ShippingMethod\ShippingMethodService;

/**
 * Class LocationServiceTest
 * @package Logeecom\Tests\BusinessLogic\Location
 */
class LocationServiceTest extends BaseTestWithServices
{
    /**
     * @var TestShopShippingMethodService
     */
    public $testShopShippingMethodService;
    /**
     * @var ShippingMethodService
     */
    public $shippingMethodService;

    public function setUp()
    {
        parent::setUp();

        /** @noinspection PhpUnhandledExceptionInspection */
        RepositoryRegistry::registerRepository(ShippingMethod::CLASS_NAME, MemoryRepository::getClassName());

        $self = $this;

        $this->testShopShippingMethodService = new TestShopShippingMethodService();
        TestServiceRegister::registerService(
            ShopShippingMethodService::CLASS_NAME,
            function () use ($self) {
                return $self->testShopShippingMethodService;
            }
        );

        TestServiceRegister::registerService(
            ShippingMethodService::CLASS_NAME,
            function () use ($self) {
                return $self->shippingMethodService;
            }
        );

        TestServiceRegister::registerService(
            LocationService::CLASS_NAME,
            function () {
                return LocationService::getInstance();
            }
        );

        TestServiceRegister::registerService(
            PackageTransformer::CLASS_NAME,
            function () {
                return PackageTransformer::getInstance();
            }
        );

        $this->shippingMethodService = ShippingMethodService::getInstance();
    }

    public function tearDown()
    {
        LocationService::resetInstance();
        ShippingMethodService::resetInstance();
        parent::tearDown();
    }

    public function testWarehouseNotSet()
    {
        $this->initShippingMethod();
        /** @var LocationService $locationService */
        $locationService = TestServiceRegister::getService(LocationService::CLASS_NAME);

        $this->assertEquals(array(), $locationService->getLocations(1, 'FR', '75008'));
    }

    public function testShippingMethodNotFound()
    {
        $this->initWarehouse();
        /** @var LocationService $locationService */
        $locationService = TestServiceRegister::getService(LocationService::CLASS_NAME);

        $this->assertEquals(array(), $locationService->getLocations(1, 'FR', '75008'));
    }

    public function testGetLocations()
    {
        $this->initShippingMethod();
        $this->initWarehouse();

        /** @var LocationService $locationService */
        $locationService = TestServiceRegister::getService(LocationService::CLASS_NAME);

        $this->httpClient->setMockResponses($this->getMockLocations());
        $dropOffs = $locationService->getLocations(1, 'FR', '75008');

        $this->assertCount(1, $dropOffs);
        $dropOffs = $dropOffs[0];

        $this->assertEquals('164047', $dropOffs['id']);
        $this->assertEquals('PARIS BANGLA INTERTIONAL', $dropOffs['name']);
        $this->assertEquals('', $dropOffs['type']);
        $this->assertEquals('FR', $dropOffs['countryCode']);
        $this->assertEquals('', $dropOffs['state']);
        $this->assertEquals('PARIS', $dropOffs['city']);
        $this->assertEquals('86. RUE DE LA CONDAMINE', $dropOffs['address']);
        $this->assertEquals(48.88465881, $dropOffs['lat']);
        $this->assertEquals(2.319819927, $dropOffs['long']);
        $this->assertEquals('', $dropOffs['phone']);

        $this->assertCount(5, $dropOffs['workingHours']);

        $this->assertEquals('11:00-14:00, 16:00-19:00', $dropOffs['workingHours']['saturday']);
    }

    public function testInvalidCountry()
    {
        $this->initShippingMethod();
        $this->initWarehouse();

        /** @var LocationService $locationService */
        $locationService = TestServiceRegister::getService(LocationService::CLASS_NAME);
        $locations = $locationService->getLocations(1, 'ES', '28009');

        $this->assertEmpty($locations);
    }

    public function testBadApiCall()
    {
        $this->initShippingMethod();
        $this->initWarehouse();

        /** @var LocationService $locationService */
        $locationService = TestServiceRegister::getService(LocationService::CLASS_NAME);
        $this->httpClient->setMockResponses($this->getBadLocations());
        $locations = $locationService->getLocations(1, 'FR', '75008');

        $this->assertEmpty($locations);
    }

    public function testGetLocationsForNonDropOffService()
    {
        $this->initShippingMethod(false);
        $this->initWarehouse();

        /** @var LocationService $locationService */
        $locationService = TestServiceRegister::getService(LocationService::CLASS_NAME);
        $this->httpClient->setMockResponses($this->getMockLocations());
        $locations = $locationService->getLocations(1, 'FR', '75008');

        $this->assertEmpty($locations);
    }

    /**
     * @expectedException \Packlink\BusinessLogic\Location\Exceptions\PlatformCountryNotSupportedException
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Packlink\BusinessLogic\Location\Exceptions\PlatformCountryNotSupportedException
     */
    public function testLocationSearchWithUnsupportedCountry()
    {
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), '[]')));

        /** @var LocationService $locationService */
        $locationService = TestServiceRegister::getService(LocationService::CLASS_NAME);
        $locationService->searchLocations('RS', 'Test');
    }

    public function testGetLocationsForInvalidPostalCode()
    {
        $this->initShippingMethod(true);
        $this->initWarehouse();

        /** @var LocationService $locationService */
        $locationService = TestServiceRegister::getService(LocationService::CLASS_NAME);
        $locations = $locationService->getLocations(1, 'NL', '1011ASZ');

        $this->assertEmpty($locations);
    }

    public function testGetLocationsForTransformedPostalCode()
    {
        $this->httpClient->setMockResponses(array(
                new HttpResponse(
                    200, array(), file_get_contents(__DIR__ . '/../Common/ApiResponses/ShippingServices/ShippingServiceDetails-IT-NL.json')
                ),
            )
        );

        $this->initShippingMethod(true);
        $this->initWarehouse();

        /** @var LocationService $locationService */
        $locationService = TestServiceRegister::getService(LocationService::CLASS_NAME);
        $locations = $locationService->getLocations(1, 'NL', '1011AS');

        $this->assertEmpty($locations);
    }

    /**
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Packlink\BusinessLogic\Location\Exceptions\PlatformCountryNotSupportedException
     */
    public function testLocationSearch()
    {
        $this->httpClient->setMockResponses($this->getMockLocationSearchResponses());

        /** @var LocationService $locationService */
        $locationService = TestServiceRegister::getService(LocationService::CLASS_NAME);

        $result = $locationService->searchLocations('DE', '3');
        $this->assertCount(1, $result);

        $info = $result[0];

        $this->assertInstanceOf('\Packlink\BusinessLogic\Http\DTO\LocationInfo', $info);

        $this->assertEquals('pc_de_44826', $info->id);
        $this->assertEquals('Deutschland', $info->state);
        $this->assertEquals('Münchenbernsdorf', $info->city);
        $this->assertEquals('07589', $info->zipcode);
        $this->assertEquals('07589 - Münchenbernsdorf', $info->text);

        $asArray = $info->toArray();

        $this->assertEquals($info->id, $asArray['id']);
        $this->assertEquals($info->state, $asArray['state']);
        $this->assertEquals($info->city, $asArray['city']);
        $this->assertEquals($info->zipcode, $asArray['zipcode']);
        $this->assertEquals($info->text, $asArray['text']);
    }

    /**
     * @param bool $isDropOff
     */
    private function initShippingMethod($isDropOff = true)
    {
        $this->shippingMethodService->add($this->getShippingServiceDetails(1, 10.73, $isDropOff));
        $this->shippingMethodService->add($this->getShippingServiceDetails(2, 13, $isDropOff));
        $this->shippingMethodService->add($this->getShippingServiceDetails(3, 7.88, $isDropOff));
        $this->shippingMethodService->add($this->getShippingServiceDetails(4, 18.25, $isDropOff));
    }

    /**
     * @param int $id
     * @param float $basePrice
     * @param bool $isDropOff
     *
     * @return \Logeecom\Infrastructure\Data\DataTransferObject|\Packlink\BusinessLogic\Http\DTO\ShippingServiceDetails
     */
    private function getShippingServiceDetails($id, $basePrice = 10.73, $isDropOff = true)
    {
        $details = ShippingServiceDetails::fromArray(
            array(
                'id' => $id,
                'carrier_name' => 'test carrier',
                'service_name' => 'test service',
                'currency' => 'EUR',
                'country' => 'FR',
                'dropoff' => false,
                'delivery_to_parcelshop' => $isDropOff,
                'category' => 'express',
                'transit_time' => '3 DAYS',
                'transit_hours' => 72,
                'first_estimated_delivery_date' => '2019-01-05',
                'national' => true,
                'price' => array(
                    'tax_price' => 3,
                    'base_price' => $basePrice,
                    'total_price' => $basePrice + 3,
                ),
            )
        );

        $details->departureCountry = 'FR';
        $details->destinationCountry = 'FR';
        $details->national = true;

        return $details;
    }

    private function initWarehouse()
    {
        $warehouse = new TestWarehouse();
        $warehouse->country = 'FR';
        $warehouse->postalCode = '75008';
        $this->shopConfig->setDefaultWarehouse($warehouse);
    }

    /**
     * @return array
     */
    private function getMockLocations()
    {
        $response = file_get_contents(__DIR__ . '/../Common/ApiResponses/dropOffs.json');

        return array(
            new HttpResponse(200, array(), ''),
            new HttpResponse(200, array(), $response),
        );
    }

    /**
     * @return array
     */
    private function getMockLocationSearchResponses()
    {
        return array(
            new HttpResponse(
                200, array(), file_get_contents(__DIR__ . '/../Common/ApiResponses/postalZones.json')
            ),
            new HttpResponse(200, array(), '[]'),
            new HttpResponse(
                200, array(), file_get_contents(__DIR__ . '/../Common/ApiResponses/locationInfo.json')
            ),
        );
    }

    /**
     * @return array
     */
    private function getBadLocations()
    {
        return array(
            new HttpResponse(200, array(), ''),
            new HttpResponse(400, array(), '[]'),
        );
    }
}
