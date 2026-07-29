<?php

namespace Logeecom\Tests\BusinessLogic\Controllers;

use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\TestFrontDtoFactory;
use Logeecom\Tests\BusinessLogic\ShippingMethod\TestShopShippingMethodService;
use Logeecom\Tests\BusinessLogic\Tasks\UpdateShippingServicesTaskTest;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Controllers\DTO\ShippingMethodConfiguration;
use Packlink\BusinessLogic\Controllers\DTO\ShippingMethodResponse;
use Packlink\BusinessLogic\Controllers\ShippingMethodController;
use Packlink\BusinessLogic\Customs\Models\CustomsMapping;
use Packlink\BusinessLogic\DDP\DdpBehavior;
use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingPricePolicy;
use Packlink\BusinessLogic\ShippingMethod\ShippingMethodService;
use Packlink\BusinessLogic\SystemInformation\SystemInfoService;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\SystemInfo\TestSystemInfoService;

/**
 * Class ShippingMethodControllerTest
 * @package Logeecom\Tests\BusinessLogic\Controllers
 */
class ShippingMethodControllerTest extends BaseTestWithServices
{
    /**
     * @var ShippingMethodController
     */
    public $controller;
    /**
     * @var ShippingMethodService
     */
    public $shippingMethodService;
    /**
     * @var TestShopShippingMethodService
     */
    public $testShopShippingMethodService;
    /**
     * @var TestSystemInfoService
     */
    public $systemInfoService;

    /**
     * @before
     * @inheritdoc
     */
    public function before()
    {
        parent::before();

        /** @noinspection PhpUnhandledExceptionInspection */
        RepositoryRegistry::registerRepository(ShippingMethod::CLASS_NAME, MemoryRepository::getClassName());

        $me = $this;
        $me->shopConfig->setAuthorizationToken('test_token');

        $me->testShopShippingMethodService = new TestShopShippingMethodService();
        TestServiceRegister::registerService(
            ShopShippingMethodService::CLASS_NAME,
            function () use ($me) {
                return $me->testShopShippingMethodService;
            }
        );

        $me->systemInfoService = new TestSystemInfoService();
        TestServiceRegister::registerService(
            SystemInfoService::CLASS_NAME,
            function () use ($me) {
                return $me->systemInfoService;
            }
        );

        $me->shippingMethodService = ShippingMethodService::getInstance();
        TestServiceRegister::registerService(
            ShippingMethodService::CLASS_NAME,
            function () use ($me) {
                return $me->shippingMethodService;
            }
        );

        $this->controller = new ShippingMethodController();
    }

    /**
     * @after
     * @inheritDoc
     */
    public function after()
    {
        ShippingMethodService::resetInstance();
        parent::after();
    }

    public function testGetAll()
    {
        $this->importShippingMethods();
        $all = $this->controller->getAll();
        $this->assertNotEmpty($all);
        $this->assertCount(19, $all);
        foreach ($all as $item) {
            $this->assertInstanceOf(
                '\Packlink\BusinessLogic\Controllers\DTO\ShippingMethodResponse',
                $item
            );
            $this->assertTrue(is_array($item->toArray()));
        }
    }

    public function testSaveChangeNameAndShowImage()
    {
        $this->importShippingMethods();
        $all = $this->controller->getAll();
        $first = $all[0];
        $shipment = new ShippingMethodConfiguration();
        $shipment->id = $first->id;
        $shipment->name = 'First name test';
        $shipment->showLogo = !$first->showLogo;
        $shipment->pricingPolicies = $first->pricingPolicies;
        $shipment->isShipToAllCountries = true;
        $shipment->shippingCountries = array();

        $model = $this->controller->save($shipment);

        $this->assertNotNull($model);

        $this->assertEquals($shipment->id, $model->id);
        $this->assertEquals($shipment->name, $model->name);
        $this->assertEquals($shipment->showLogo, $model->showLogo);
        $this->assertEquals($shipment->pricingPolicies, $model->pricingPolicies);
    }

    public function testDefaultCurrencyConfiguration()
    {
        $this->importShippingMethods();
        $all = $this->controller->getAll();
        $first = $all[0];
        $shipment = new ShippingMethodConfiguration();
        $shipment->id = $first->id;
        $shipment->name = 'First name test';
        $shipment->showLogo = !$first->showLogo;
        $shipment->pricingPolicies = array(
            ShippingPricePolicy::fromArray(array(
                'range_type' => ShippingPricePolicy::RANGE_PRICE,
                'from_price' => 0,
                'to_price' => 20,
                'pricing_policy' => ShippingPricePolicy::POLICY_PACKLINK,
                'system_id' => 'default',
            )),
        );
        $shipment->isShipToAllCountries = true;

        self::assertNotNull($this->controller->save($shipment));
    }

    public function testSingleStoreCurrencyConfiguration()
    {
        $this->importShippingMethods();
        $all = $this->controller->getAll();
        $first = $all[0];
        $shipment = new ShippingMethodConfiguration();
        $shipment->id = $first->id;
        $shipment->name = 'First name test';
        $shipment->showLogo = !$first->showLogo;
        $shipment->pricingPolicies = array(
            ShippingPricePolicy::fromArray(array(
                'range_type' => ShippingPricePolicy::RANGE_PRICE,
                'from_price' => 0,
                'to_price' => 20,
                'pricing_policy' => ShippingPricePolicy::POLICY_PACKLINK,
                'system_id' => 'test',
            )),
        );
        $shipment->isShipToAllCountries = true;

        self::assertNotNull($this->controller->save($shipment));
    }

    public function testMisconfiguredSingleStoreCurrencyConfiguration()
    {
        /** @var TestSystemInfoService $testSystemInfoService */
        $testSystemInfoService = TestServiceRegister::getService(SystemInfoService::CLASS_NAME);
        $testSystemInfoService->setInvalid(true);

        $this->importShippingMethods();
        $all = $this->controller->getAll();
        $first = $all[0];
        $shipment = new ShippingMethodConfiguration();
        $shipment->id = $first->id;
        $shipment->name = 'First name test';
        $shipment->showLogo = !$first->showLogo;
        $shipment->fixedPrices = array(
            'test' => 5
        );
        $shipment->pricingPolicies = array(
            ShippingPricePolicy::fromArray(array(
                'range_type' => ShippingPricePolicy::RANGE_PRICE,
                'from_price' => 0,
                'to_price' => 20,
                'pricing_policy' => ShippingPricePolicy::POLICY_FIXED_PRICE,
                'fixed_price' => 43.98,
                'system_id' => 'test',
            )),
        );
        $shipment->isShipToAllCountries = true;

        self::assertNotNull($this->controller->save($shipment));
    }

    public function testInvalidMisconfiguredSingleStoreCurrencyConfiguration()
    {
        /** @var TestSystemInfoService $testSystemInfoService */
        $testSystemInfoService = TestServiceRegister::getService(SystemInfoService::CLASS_NAME);
        $testSystemInfoService->setInvalid(true);

        $this->importShippingMethods();
        $all = $this->controller->getAll();
        $first = $all[0];
        $shipment = new ShippingMethodConfiguration();
        $shipment->id = $first->id;
        $shipment->name = 'First name test';
        $shipment->showLogo = !$first->showLogo;
        $shipment->pricingPolicies = array(
            ShippingPricePolicy::fromArray(array(
                'range_type' => ShippingPricePolicy::RANGE_PRICE,
                'from_price' => 0,
                'to_price' => 20,
                'pricing_policy' => ShippingPricePolicy::POLICY_FIXED_PRICE,
                'fixed_price' => 43.98,
                'system_id' => 'test',
            )),
        );
        $shipment->isShipToAllCountries = true;

        self::assertNull($this->controller->save($shipment));
    }

    public function testValidMultistoreCurrencyConfiguration()
    {
        /** @var TestSystemInfoService $testSystemInfoService */
        $testSystemInfoService = TestServiceRegister::getService(SystemInfoService::CLASS_NAME);
        $testSystemInfoService->setMultistore(true);

        $this->importShippingMethods();
        $all = $this->controller->getAll();
        $first = $all[0];
        $shipment = new ShippingMethodConfiguration();
        $shipment->id = $first->id;
        $shipment->name = 'First name test';
        $shipment->showLogo = !$first->showLogo;
        $shipment->fixedPrices = array(
            'test' => 5,
            'test1' => 10,
        );
        $shipment->systemDefaults = array(
            'test' => false,
            'test1' => false,
        );
        $shipment->pricingPolicies = array(
            ShippingPricePolicy::fromArray(array(
                'range_type' => ShippingPricePolicy::RANGE_PRICE,
                'from_price' => 0,
                'to_price' => 20,
                'pricing_policy' => ShippingPricePolicy::POLICY_FIXED_PRICE,
                'fixed_price' => 43.98,
                'system_id' => 'test',
            )),
        );
        $shipment->isShipToAllCountries = true;

        self::assertNotNull($this->controller->save($shipment));
    }

    public function testNoDefaultPriceInMultistoreCurrencyConfiguration()
    {
        /** @var TestSystemInfoService $testSystemInfoService */
        $testSystemInfoService = TestServiceRegister::getService(SystemInfoService::CLASS_NAME);
        $testSystemInfoService->setMultistore(true);

        $this->importShippingMethods();
        $all = $this->controller->getAll();
        $first = $all[0];
        $shipment = new ShippingMethodConfiguration();
        $shipment->id = $first->id;
        $shipment->name = 'First name test';
        $shipment->showLogo = !$first->showLogo;
        $shipment->fixedPrices = array(
            'test1' => 10
        );
        $shipment->systemDefaults = array(
            'test' => true,
            'test1' => true,
        );
        $shipment->pricingPolicies = array(
            ShippingPricePolicy::fromArray(array(
                'range_type' => ShippingPricePolicy::RANGE_PRICE,
                'from_price' => 0,
                'to_price' => 20,
                'pricing_policy' => ShippingPricePolicy::POLICY_FIXED_PRICE,
                'fixed_price' => 43.98,
                'system_id' => 'test',
            )),
        );
        $shipment->isShipToAllCountries = true;

        self::assertNull($this->controller->save($shipment));
    }

    public function testFallbackToDefaultMultistoreCurrencyConfiguration()
    {
        /** @var TestSystemInfoService $testSystemInfoService */
        $testSystemInfoService = TestServiceRegister::getService(SystemInfoService::CLASS_NAME);
        $testSystemInfoService->setMultistore(true);

        $this->importShippingMethods();
        $all = $this->controller->getAll();
        $first = $all[0];
        $shipment = new ShippingMethodConfiguration();
        $shipment->id = $first->id;
        $shipment->name = 'First name test';
        $shipment->showLogo = !$first->showLogo;
        $shipment->fixedPrices = array(
            'default' => 10
        );
        $shipment->systemDefaults = array(
            'test' => true,
            'test1' => true,
        );
        $shipment->pricingPolicies = array(
            ShippingPricePolicy::fromArray(array(
                'range_type' => ShippingPricePolicy::RANGE_PRICE,
                'from_price' => 0,
                'to_price' => 20,
                'pricing_policy' => ShippingPricePolicy::POLICY_FIXED_PRICE,
                'fixed_price' => 43.98,
                'system_id' => 'test',
            )),
        );
        $shipment->isShipToAllCountries = true;

        self::assertNotNull($this->controller->save($shipment));
    }

    public function testInvalidMultistoreCurrencyConfiguration()
    {
        /** @var TestSystemInfoService $testSystemInfoService */
        $testSystemInfoService = TestServiceRegister::getService(SystemInfoService::CLASS_NAME);
        $testSystemInfoService->setMultistore(true);

        $this->importShippingMethods();
        $all = $this->controller->getAll();
        $first = $all[0];
        $shipment = new ShippingMethodConfiguration();
        $shipment->id = $first->id;
        $shipment->name = 'First name test';
        $shipment->showLogo = !$first->showLogo;
        $shipment->pricingPolicies = array(
            ShippingPricePolicy::fromArray(array(
                'range_type' => ShippingPricePolicy::RANGE_PRICE,
                'from_price' => 0,
                'to_price' => 20,
                'pricing_policy' => ShippingPricePolicy::POLICY_PACKLINK,
                'system_id' => 'test',
            )),
            ShippingPricePolicy::fromArray(array(
                'range_type' => ShippingPricePolicy::RANGE_PRICE,
                'from_price' => 0,
                'to_price' => 20,
                'pricing_policy' => ShippingPricePolicy::POLICY_PACKLINK,
                'system_id' => 'test1',
            )),
        );
        $shipment->isShipToAllCountries = true;

        self::assertNull($this->controller->save($shipment));
    }

    /**
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function testShippingMethodConfigurationToArray()
    {
        $instance = new ShippingMethodConfiguration();
        $instance->id = 12;
        $instance->name = 'First name test';
        $instance->showLogo = true;
        $instance->pricingPolicies[] = ShippingPricePolicy::fromArray(
            array(
                'range_type' => ShippingPricePolicy::RANGE_PRICE,
                'from_price' => 0,
                'to_price' => 20,
                'pricing_policy' => ShippingPricePolicy::POLICY_PACKLINK,
            )
        );

        $data = $instance->toArray();

        self::assertNotEmpty($data);
        self::assertEquals($instance->id, $data['id']);
        self::assertEquals($instance->name, $data['name']);
        self::assertEquals($instance->showLogo, $data['showLogo']);
        self::assertCount(1, $instance->pricingPolicies);
    }

    /**
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function testShippingMethodResponseToArray()
    {
        $instance = new ShippingMethodResponse();
        $instance->id = 12;
        $instance->name = 'First name test';
        $instance->type = 'national';
        $instance->carrierName = 'carrier';
        $instance->deliveryDescription = 'description';
        $instance->parcelOrigin = 'pick-up';
        $instance->parcelDestination = 'drop-off';
        $instance->logoUrl = 'url';
        $instance->showLogo = false;
        $instance->pricingPolicies[] = ShippingPricePolicy::fromArray(
            array(
                'range_type' => ShippingPricePolicy::RANGE_PRICE,
                'from_price' => 0,
                'to_price' => 20,
                'pricing_policy' => ShippingPricePolicy::POLICY_PACKLINK,
            )
        );

        $data = $instance->toArray();

        self::assertNotEmpty($data);
        self::assertEquals($instance->id, $data['id']);
        self::assertEquals($instance->name, $data['name']);
        self::assertEquals($instance->type, $data['type']);
        self::assertEquals($instance->carrierName, $data['carrierName']);
        self::assertEquals($instance->deliveryDescription, $data['deliveryDescription']);
        self::assertEquals($instance->parcelOrigin, $data['parcelOrigin']);
        self::assertEquals($instance->parcelDestination, $data['parcelDestination']);
        self::assertEquals($instance->logoUrl, $data['logoUrl']);
        self::assertEquals($instance->showLogo, $data['showLogo']);
        self::assertCount(1, $instance->pricingPolicies);
    }

    public function testShippingMethodConfigurationDdpDefaults()
    {
        $instance = ShippingMethodConfiguration::fromArray(
            array(
                'id' => 12,
                'name' => 'First name test',
                'showLogo' => true,
            )
        );

        self::assertEquals(DdpBehavior::NONE, $instance->ddpBehavior);
        self::assertNull($instance->ddpAdjustmentType);
        self::assertSame(0.0, $instance->ddpAdjustmentAmount);

        $data = $instance->toArray();

        self::assertEquals(DdpBehavior::NONE, $data['ddpBehavior']);
        self::assertNull($data['ddpAdjustmentType']);
        self::assertSame(0.0, $data['ddpAdjustmentAmount']);
    }

    public function testShippingMethodConfigurationDdpRoundTrip()
    {
        $instance = ShippingMethodConfiguration::fromArray(
            array(
                'id' => 12,
                'name' => 'First name test',
                'showLogo' => true,
                'ddpBehavior' => DdpBehavior::OPTIONAL,
                'ddpAdjustmentType' => 'percentage',
                'ddpAdjustmentAmount' => -10,
            )
        );

        $data = $instance->toArray();

        self::assertEquals(DdpBehavior::OPTIONAL, $data['ddpBehavior']);
        self::assertEquals('percentage', $data['ddpAdjustmentType']);
        self::assertEquals(-10, $data['ddpAdjustmentAmount']);
    }

    public function testSaveDdpConfiguration()
    {
        $this->systemInfoService->setInvalid(false);
        $this->systemInfoService->setMultistore(false);
        $this->importShippingMethods();
        $all = $this->controller->getAll();
        $first = $all[0];
        $this->setDdpSupportLevel($first->id, DdpBehavior::LEVEL_SUPPORTED);

        $shipment = $this->getDdpShipmentConfiguration($first);
        $shipment->ddpBehavior = DdpBehavior::OPTIONAL;
        $shipment->ddpAdjustmentType = 'percentage';
        $shipment->ddpAdjustmentAmount = -10;

        $model = $this->controller->save($shipment);

        $this->assertNotNull($model);
        $this->assertEquals(DdpBehavior::OPTIONAL, $model->ddpBehavior);
        $this->assertEquals('percentage', $model->ddpAdjustmentType);
        $this->assertEquals(-10.0, $model->ddpAdjustmentAmount);
        $this->assertEquals(DdpBehavior::LEVEL_SUPPORTED, $model->ddpSupportLevel);

        $data = $model->toArray();

        $this->assertEquals(DdpBehavior::LEVEL_SUPPORTED, $data['ddpSupportLevel']);
        $this->assertFalse($data['customsConfigured']);
    }

    public function testSaveInvalidDdpConfiguration()
    {
        $this->systemInfoService->setInvalid(false);
        $this->systemInfoService->setMultistore(false);
        $this->importShippingMethods();
        $all = $this->controller->getAll();
        $first = $all[0];
        $this->setDdpSupportLevel($first->id, DdpBehavior::LEVEL_SUPPORTED);

        $invalidCases = array(
            array('ddpBehavior' => 'unknown'),
            array('ddpBehavior' => DdpBehavior::MANDATORY),
            array('ddpAdjustmentType' => 'unknown'),
            array('ddpAdjustmentAmount' => 'abc'),
            array('ddpAdjustmentType' => 'percentage', 'ddpAdjustmentAmount' => -100),
        );

        foreach ($invalidCases as $case) {
            $shipment = $this->getDdpShipmentConfiguration($first);
            foreach ($case as $property => $value) {
                $shipment->$property = $value;
            }

            $this->assertNull($this->controller->save($shipment));
        }
    }

    public function testSaveDdpBehaviorOnUnsupportedMethod()
    {
        $this->systemInfoService->setInvalid(false);
        $this->systemInfoService->setMultistore(false);
        $this->importShippingMethods();
        $all = $this->controller->getAll();
        $first = $all[0];
        $this->setDdpSupportLevel($first->id, null);

        $shipment = $this->getDdpShipmentConfiguration($first);
        $shipment->ddpBehavior = DdpBehavior::ENFORCED;

        $this->assertNull($this->controller->save($shipment));
    }

    public function testResponseCustomsNotConfigured()
    {
        $this->importShippingMethods();
        $all = $this->controller->getAll();

        $this->assertFalse($all[0]->customsConfigured);

        $model = $this->shippingMethodService->getShippingMethod($all[0]->id);
        $this->assertEquals($model->getDdpSupportLevel(), $all[0]->ddpSupportLevel);
    }

    /**
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoFactoryRegistrationException
     */
    public function testResponseCustomsConfigured()
    {
        $this->importShippingMethods();
        TestFrontDtoFactory::register(CustomsMapping::CLASS_KEY, CustomsMapping::CLASS_NAME);
        $this->shopConfig->setCustomsMappings(
            CustomsMapping::fromArray(
                array(
                    'default_reason' => 'PURCHASE_OR_SALE',
                    'default_sender_tax_id' => '123',
                    'default_receiver_tax_id' => '123',
                    'default_receiver_user_type' => 'PRIVATE_PERSON',
                    'default_tariff_number' => '123456',
                    'default_country' => 'FR',
                    'mapping_receiver_tax_id' => 'tax_1',
                )
            )
        );

        $all = $this->controller->getAll();

        $this->assertTrue($all[0]->customsConfigured);
    }

    public function testSaveNoShippingMethod()
    {
        $shipment = new ShippingMethodConfiguration();
        $shipment->id = 1235412;
        $shipment->name = 'First name test';
        $shipment->showLogo = true;

        $this->assertNull($this->controller->save($shipment));
    }

    public function testSaveInvalidMissingProperty()
    {
        $shipment = new ShippingMethodConfiguration();
        $properties = array('id', 'name', 'showLogo', 'pricingPolicies');
        $shipment->id = 1235412;
        $shipment->name = 'First name test';
        $shipment->showLogo = true;

        foreach ($properties as $property) {
            $value = $shipment->$property;
            $shipment->$property = null;

            $this->assertNull($this->controller->save($shipment));

            $shipment->$property = $value;
        }
    }

    public function testSaveInvalidPropertyWrongType()
    {
        $shipment = new ShippingMethodConfiguration();
        $properties = array('id' => 'abc', 'name' => true, 'showLogo' => 12.5, 'pricingPolicies' => 'asdf');
        $shipment->id = 1235412;
        $shipment->name = 'First name test';
        $shipment->showLogo = true;

        foreach ($properties as $property => $value) {
            $oldValue = $shipment->$property;
            $shipment->$property = $value;

            $this->assertNull($this->controller->save($shipment));

            $shipment->$property = $oldValue;
        }
    }

    public function testActivate()
    {
        $this->importShippingMethods();
        $all = $this->controller->getAll();
        $first = $all[0];

        $this->assertTrue($this->controller->activate($first->id));
    }

    public function testActivateFakeId()
    {
        $this->assertFalse($this->controller->activate(124124152));
        $this->assertFalse($this->controller->activate('test'));
    }

    public function testDeactivate()
    {
        $this->importShippingMethods();
        $all = $this->controller->getAll();
        $first = $all[0];

        $this->assertTrue($this->controller->deactivate($first->id));
    }

    public function testDeactivateFakeId()
    {
        $this->assertFalse($this->controller->deactivate(124124152));
        $this->assertFalse($this->controller->deactivate('test'));
    }

    /**
     * Creates a valid shipping method configuration from a response DTO.
     *
     * @param ShippingMethodResponse $method Shipping method response.
     *
     * @return ShippingMethodConfiguration Shipping method configuration.
     */
    private function getDdpShipmentConfiguration(ShippingMethodResponse $method)
    {
        $shipment = new ShippingMethodConfiguration();
        $shipment->id = $method->id;
        $shipment->name = 'DDP name test';
        $shipment->showLogo = $method->showLogo;
        $shipment->pricingPolicies = $method->pricingPolicies;
        $shipment->isShipToAllCountries = true;
        $shipment->shippingCountries = array();

        return $shipment;
    }

    /**
     * Sets the DDP support level on all services of the given shipping method.
     *
     * @param int $id Shipping method identifier.
     * @param string $level DDP support level.
     */
    private function setDdpSupportLevel($id, $level)
    {
        $model = $this->shippingMethodService->getShippingMethod($id);
        $services = $model->getShippingServices();
        foreach ($services as $service) {
            $service->setDdpSupportLevel($level);
        }

        $model->setShippingServices($services);
        $this->shippingMethodService->save($model);
    }

    /**
     * Calls other test to populate shipping methods storage.
     */
    private function importShippingMethods()
    {
        $test = new UpdateShippingServicesTaskTest('before');
        /** @noinspection PhpUnhandledExceptionInspection */
        $test->before();
        /** @noinspection PhpUnhandledExceptionInspection */
        $test->testExecuteAllNew();
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->before();
    }
}
