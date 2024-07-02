<?php

namespace Logeecom\Tests\BusinessLogic\Controllers;

use Logeecom\Infrastructure\Configuration\ConfigEntity;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\TestFrontDtoFactory;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\TestShopConfiguration;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Controllers\DTO\ModuleState;
use Packlink\BusinessLogic\Controllers\ModuleStateController;
use Packlink\BusinessLogic\DTO\ValidationError;
use Packlink\BusinessLogic\Http\DTO\ParcelInfo;
use Packlink\BusinessLogic\Warehouse\Warehouse;

/**
 * Class DashboardControllerTest.
 *
 * @package Logeecom\Tests\BusinessLogic\Controllers
 */
class ModuleStateControllerTest extends BaseTestWithServices
{
    /**
     * @var ModuleStateController
     */
    private $moduleStateController;

    /**
     * @before
     * @inheritdoc
     */
    public function before()
    {
        RepositoryRegistry::registerRepository(ConfigEntity::CLASS_NAME, MemoryRepository::getClassName());
        TestFrontDtoFactory::register(ValidationError::CLASS_KEY, ValidationError::CLASS_NAME);
        TestFrontDtoFactory::register(Warehouse::CLASS_KEY, Warehouse::CLASS_NAME);
        TestFrontDtoFactory::register(ParcelInfo::CLASS_KEY, ParcelInfo::CLASS_NAME);

        $this->moduleStateController = new ModuleStateController();
    }

    /**
     * Tests the case when auth key is not set.
     */
    public function testStateNoAuthKey()
    {
        $configuration = new TestShopConfiguration();

        new TestServiceRegister(
            array(
                Configuration::CLASS_NAME => function () use ($configuration) {
                    return $configuration;
                }
            )
        );

        $result = $this->moduleStateController->getCurrentState();

        $this->assertEquals(ModuleState::LOGIN_STATE, $result->state);
    }

    /**
     * Tests the case when auth key is set and default warehouse is not set.
     */
    public function testStateAuthKeySet_DefaultWarehouseNotSet()
    {
        $configuration = new TestShopConfiguration();

        $configuration->setAuthorizationToken('validToken');

        new TestServiceRegister(
            array(
                Configuration::CLASS_NAME => function () use ($configuration) {
                    return $configuration;
                }
            )
        );

        $result = $this->moduleStateController->getCurrentState();

        $this->assertEquals(ModuleState::ONBOARDING_STATE, $result->state);
    }

    /**
     * Tests the case when auth key, default warehouse and parcel are set.
     */
    public function testStateAuthKeySet_DefaultWarehouseAndParcelSet()
    {
        $configuration = new TestShopConfiguration();

        $configuration->setAuthorizationToken('validToken');
        $this->createDefaultParcel($configuration);
        $this->createDefaultWarehouse($configuration);

        new TestServiceRegister(
            array(
                Configuration::CLASS_NAME => function () use ($configuration) {
                    return $configuration;
                }
            )
        );

        $result = $this->moduleStateController->getCurrentState();

        $this->assertEquals(ModuleState::SERVICES_STATE, $result->state);
    }

    /**
     * @param TestShopConfiguration $configuration
     */
    protected function createDefaultParcel(TestShopConfiguration $configuration)
    {
        $parcel = new ParcelInfo();
        $parcel->default = true;
        $parcel->weight = 20;
        $parcel->height = 20;
        $parcel->length = 20;
        $parcel->width = 20;
        $configuration->setDefaultParcel($parcel);
    }

    /**
     * @param TestShopConfiguration $configuration
     */
    protected function createDefaultWarehouse(TestShopConfiguration $configuration)
    {
        $warehouse = new Warehouse();
        $warehouse->default = true;
        $warehouse->address = 'test 12';
        $warehouse->city = 'Test';
        $warehouse->alias = 'Test';
        $warehouse->company = 'Test';
        $warehouse->country = 'Test';
        $warehouse->email = 'test@test.com';
        $warehouse->id = '1';
        $warehouse->phone = '011/1111111';
        $warehouse->name = 'test';
        $warehouse->postalCode = '11111';
        $warehouse->surname = 'test';
        $configuration->setDefaultWarehouse($warehouse);
    }
}
