<?php

namespace Logeecom\Tests\BusinessLogic\Controllers;

use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Tests\BusinessLogic\ShippingMethod\TestShopShippingMethodService;
use Logeecom\Tests\Common\BaseTestWithServices;
use Logeecom\Tests\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Common\TestComponents\TestHttpClient;
use Logeecom\Tests\Common\TestServiceRegister;
use Packlink\BusinessLogic\Controllers\DashboardController;
use Packlink\BusinessLogic\Controllers\DTO\DashboardStatus;
use Packlink\BusinessLogic\Http\DTO\ParcelInfo;
use Packlink\BusinessLogic\Http\DTO\Warehouse;
use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\ShippingMethod\ShippingMethodService;

/**
 * Class DashboardControllerTest
 * @package Logeecom\Tests\BusinessLogic\Controllers
 */
class DashboardControllerTest extends BaseTestWithServices
{
    /**
     * @var DashboardController
     */
    private $dashboardController;
    /**
     * @var ShippingMethodService
     */
    private $shippingMethodService;
    /**
     * @var TestShopShippingMethodService
     */
    private $testShopShippingMethodService;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        /** @noinspection PhpUnhandledExceptionInspection */
        RepositoryRegistry::registerRepository(ShippingMethod::CLASS_NAME, MemoryRepository::getClassName());

        $taskInstance = $this;
        $taskInstance->shopConfig->setAuthorizationToken('test_token');

        $httpClient = new TestHttpClient();
        TestServiceRegister::registerService(
            HttpClient::CLASS_NAME,
            function () use ($httpClient) {
                return $httpClient;
            }
        );

        $taskInstance->testShopShippingMethodService = new TestShopShippingMethodService();
        TestServiceRegister::registerService(
            ShopShippingMethodService::CLASS_NAME,
            function () use ($taskInstance) {
                return $taskInstance->testShopShippingMethodService;
            }
        );

        $taskInstance->shippingMethodService = ShippingMethodService::getInstance();
        TestServiceRegister::registerService(
            ShippingMethodService::CLASS_NAME,
            function () use ($taskInstance) {
                return $taskInstance->shippingMethodService;
            }
        );

        $this->dashboardController = new DashboardController();
    }

    protected function tearDown()
    {
        ShippingMethodService::resetInstance();
        parent::tearDown();
    }

    public function testGetStatusNothingSet()
    {
        $status = $this->dashboardController->getStatus();

        $this->assertInstanceOf(DashboardStatus::CLASS_NAME, $status);
        $this->assertFalse($status->isParcelSet);
        $this->assertFalse($status->isWarehouseSet);
        $this->assertFalse($status->isShippingMethodSet);

        $asArray = $status->toArray();

        $this->assertArrayHasKey('parcelSet', $asArray);
        $this->assertFalse($asArray['parcelSet']);
        $this->assertArrayHasKey('warehouseSet', $asArray);
        $this->assertFalse($asArray['warehouseSet']);
        $this->assertArrayHasKey('shippingMethodSet', $asArray);
        $this->assertFalse($asArray['shippingMethodSet']);
    }

    public function testGetStatusShippingNotSet()
    {
        $this->shopConfig->setDefaultWarehouse(new Warehouse());
        $this->shopConfig->setDefaultParcel(new ParcelInfo());

        $status = $this->dashboardController->getStatus();

        $this->assertInstanceOf(DashboardStatus::CLASS_NAME, $status);
        $this->assertTrue($status->isParcelSet);
        $this->assertTrue($status->isWarehouseSet);
        $this->assertFalse($status->isShippingMethodSet);
    }

    public function testGetStatusAllSet()
    {
        $shippingMethod = new ShippingMethod();
        $shippingMethod->setActivated(true);
        $shippingMethod->setServiceId(123);
        $shippingMethod->setEnabled(false);
        $shippingMethod->setDepartureDropOff(false);
        $shippingMethod->setDestinationDropOff(false);
        $shippingMethod->setNational(true);
        $shippingMethod->setExpressDelivery(true);

        /** @noinspection PhpUnhandledExceptionInspection */
        RepositoryRegistry::getRepository(ShippingMethod::CLASS_NAME)->save($shippingMethod);

        $this->shopConfig->setDefaultWarehouse(new Warehouse());
        $this->shopConfig->setDefaultParcel(new ParcelInfo());

        $status = $this->dashboardController->getStatus();

        $this->assertInstanceOf(DashboardStatus::CLASS_NAME, $status);
        $this->assertTrue($status->isParcelSet);
        $this->assertTrue($status->isWarehouseSet);
        $this->assertTrue($status->isShippingMethodSet);
    }
}
