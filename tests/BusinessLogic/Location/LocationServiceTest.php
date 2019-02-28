<?php

namespace Logeecom\Tests\BusinessLogic\Location;

use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\BusinessLogic\ShippingMethod\TestShopShippingMethodService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestHttpClient;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Http\DTO\ShippingServiceDetails;
use Packlink\BusinessLogic\Http\DTO\Warehouse;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\Location\LocationService;
use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\ShippingMethod\ShippingMethodService;

/**
 * Class LocationServiceTest
 * @package Logeecom\Tests\BusinessLogic\Location
 */
class LocationServiceTest extends BaseTestWithServices
{
    /**
     * @var TestHttpClient
     */
    public $httpClient;
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
                $config = TestServiceRegister::getService(Configuration::CLASS_NAME);

                return new Proxy($config->getAuthorizationToken(), $self->httpClient);
            }
        );

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
        $this->assertEquals( 2.319819927, $dropOffs['long']);
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

    private function initShippingMethod()
    {
        $this->shippingMethodService->add($this->getShippingServiceDetails(1));
        $this->shippingMethodService->add($this->getShippingServiceDetails(2, 13));
        $this->shippingMethodService->add($this->getShippingServiceDetails(3, 7.88));
        $this->shippingMethodService->add($this->getShippingServiceDetails(4, 18.25));
    }

    private function getShippingServiceDetails($id, $basePrice = 10.73)
    {
        $details = ShippingServiceDetails::fromArray(
            array(
                'id' => $id,
                'carrier_name' => 'test carrier',
                'service_name' => 'test service',
                'currency' => 'EUR',
                'country' => 'FR',
                'dropoff' => false,
                'delivery_to_parcelshop' => false,
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
        $this->shopConfig->setDefaultWarehouse(
            Warehouse::fromArray(array('country' => 'FR', 'postal_code' => '75008'))
        );
    }

    private function getMockLocations()
    {
        $response = file_get_contents(__DIR__ . '/../Common/ApiResponses/dropOffs.json');

        return array(
            new HttpResponse(200, array(), ''),
            new HttpResponse(200, array(), $response),
        );
    }

    private function getBadLocations()
    {
        return array(
            new HttpResponse(200, array(), ''),
            new HttpResponse(400, array(), '[]'),
        );
    }
}
