<?php

namespace Logeecom\Tests\BusinessLogic\Controllers;

use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Locations\MockLocationService;
use Logeecom\Tests\BusinessLogic\ShippingMethod\TestShopShippingMethodService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Controllers\LocationsController;
use Packlink\BusinessLogic\Location\LocationService;
use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\ShippingMethod\ShippingMethodService;

class LocationsControllerTest extends BaseTestWithServices
{
    public $service;
    public $controller;
    public $testShopShippingMethodService;
    public $shippingMethodService;

    /**
     * @before
     * @inheritDoc
     */
    protected function before()
    {
        parent::before();

        RepositoryRegistry::registerRepository(ShippingMethod::CLASS_NAME, MemoryRepository::getClassName());

        $this->testShopShippingMethodService = new TestShopShippingMethodService();

        $me = $this;

        TestServiceRegister::registerService(
            ShopShippingMethodService::CLASS_NAME,
            function () use ($me) {
                return $me->testShopShippingMethodService;
            }
        );

        $this->shippingMethodService = ShippingMethodService::getInstance();
        TestServiceRegister::registerService(
            ShippingMethodService::CLASS_NAME,
            function () use ($me) {
                return $me->shippingMethodService;
            }
        );

        $this->service = MockLocationService::getInstance();
        TestServiceRegister::registerService(
            LocationService::CLASS_NAME,
            function () use ($me) {
                return $me->service;
            }
        );

        $this->controller = new LocationsController();
    }

    public function testEmptyCountry()
    {
        // arrange
        $payload = array('query' => 'test');

        // act
        $result = $this->controller->searchLocations($payload);

        // assert
        $this->assertEmpty($result);
    }

    public function testEmptyQuery()
    {
        // arrange
        $payload = array('country' => 'test');

        // act
        $result = $this->controller->searchLocations($payload);

        // assert
        $this->assertEmpty($result);
    }

    public function testEmptyCountryAndQuery()
    {
        // arrange
        $payload = array();

        // act
        $result = $this->controller->searchLocations($payload);

        // assert
        $this->assertEmpty($result);
    }

    public function testMethodCalls()
    {
        // arrange
        $payload = array('country' => 'test country', 'query' => 'test query');
        $expected = array(array('searchLocations' => array('test country', 'test query')));

        // act
        $this->controller->searchLocations($payload);

        // assert
        $this->assertEquals($expected, $this->service->callHistory);
    }

    public function testServiceFailed()
    {
        // arrange
        $this->service->shouldFail = true;
        $payload = array('country' => 'test country', 'query' => 'test query');

        // act
        $result = $this->controller->searchLocations($payload);

        // assert
        $this->assertEmpty($result);
    }

    public function testResult()
    {
        // arrange
        $this->service->searchLocationsResult = array('t1', 't2', 't3');
        $payload = array('country' => 'test country', 'query' => 'test query');

        // act
        $result = $this->controller->searchLocations($payload);

        // assert
        $this->assertEquals($this->service->searchLocationsResult, $result);
    }

    /**
     * @after
     * @inheritDoc
     */
    protected function after()
    {
        parent::after();

        MockLocationService::resetInstance();
    }
}
