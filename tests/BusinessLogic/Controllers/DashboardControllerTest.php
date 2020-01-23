<?php

namespace Logeecom\Tests\BusinessLogic\Controllers;

use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\TestFrontDtoFactory;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\TestWarehouse;
use Logeecom\Tests\BusinessLogic\ShippingMethod\TestShopShippingMethodService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Controllers\DashboardController;
use Packlink\BusinessLogic\Controllers\DTO\DashboardStatus;
use Packlink\BusinessLogic\Http\DTO\ParcelInfo;
use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\ShippingMethod\ShippingMethodService;

/**
 * Class DashboardControllerTest.
 *
 * @package Logeecom\Tests\BusinessLogic\Controllers
 */
class DashboardControllerTest extends BaseTestWithServices
{
    /**
     * @var DashboardController
     */
    public $dashboardController;
    /**
     * @var ShippingMethodService
     */
    public $shippingMethodService;
    /**
     * @var TestShopShippingMethodService
     */
    public $testShopShippingMethodService;

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
        TestFrontDtoFactory::register(DashboardStatus::CLASS_KEY, DashboardStatus::CLASS_NAME);
    }

    protected function tearDown()
    {
        ShippingMethodService::resetInstance();
        parent::tearDown();
    }

    /**
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function testGetStatusNothingSet()
    {
        $status = $this->dashboardController->getStatus();

        $this->assertInstanceOf(DashboardStatus::CLASS_NAME, $status);
        $this->assertFalse($status->isParcelSet);
        $this->assertFalse($status->isWarehouseSet);
        $this->assertFalse($status->isShippingMethodSet);

        $asArray = $status->toArray();

        $this->assertArrayHasKey('isParcelSet', $asArray);
        $this->assertFalse($asArray['isParcelSet']);
        $this->assertArrayHasKey('isWarehouseSet', $asArray);
        $this->assertFalse($asArray['isWarehouseSet']);
        $this->assertArrayHasKey('isShippingMethodSet', $asArray);
        $this->assertFalse($asArray['isShippingMethodSet']);
    }

    /**
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function testGetStatusShippingNotSet()
    {
        $this->shopConfig->setDefaultWarehouse(new TestWarehouse());
        $this->shopConfig->setDefaultParcel(ParcelInfo::defaultParcel());

        $status = $this->dashboardController->getStatus();

        $this->assertTrue($status->isParcelSet);
        $this->assertTrue($status->isWarehouseSet);
        $this->assertFalse($status->isShippingMethodSet);
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function testGetStatusAllSet()
    {
        $shippingMethod = new ShippingMethod();
        $shippingMethod->setActivated(true);
        $shippingMethod->setEnabled(false);
        $shippingMethod->setDepartureDropOff(false);
        $shippingMethod->setDestinationDropOff(false);
        $shippingMethod->setNational(true);
        $shippingMethod->setExpressDelivery(true);

        /** @noinspection PhpUnhandledExceptionInspection */
        RepositoryRegistry::getRepository(ShippingMethod::CLASS_NAME)->save($shippingMethod);

        $this->shopConfig->setDefaultWarehouse(new TestWarehouse());
        $this->shopConfig->setDefaultParcel(ParcelInfo::defaultParcel());

        $status = $this->dashboardController->getStatus();

        $this->assertTrue($status->isParcelSet);
        $this->assertTrue($status->isWarehouseSet);
        $this->assertTrue($status->isShippingMethodSet);
    }
}
