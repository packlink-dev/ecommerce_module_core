<?php

namespace Logeecom\Tests\BusinessLogic\Controllers;

use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\BusinessLogic\ShippingMethod\TestShopShippingMethodService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Controllers\DTO\ShippingMethodConfiguration;
use Packlink\BusinessLogic\Controllers\DTO\ShippingMethodResponse;
use Packlink\BusinessLogic\Controllers\ShippingMethodController;
use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;
use Packlink\BusinessLogic\ShippingMethod\Models\FixedPricePolicy;
use Packlink\BusinessLogic\ShippingMethod\Models\PercentPricePolicy;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\ShippingMethod\ShippingMethodService;
use Packlink\Tests\BusinessLogic\Tasks\UpdateShippingServicesTaskTest;

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
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

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

        $me->shippingMethodService = ShippingMethodService::getInstance();
        TestServiceRegister::registerService(
            ShippingMethodService::CLASS_NAME,
            function () use ($me) {
                return $me->shippingMethodService;
            }
        );

        $this->controller = new ShippingMethodController();
    }

    public function tearDown()
    {
        ShippingMethodService::resetInstance();
        parent::tearDown();
    }

    public function testGetAll()
    {
        $this->importShippingMethods();
        $all = $this->controller->getAll();
        $this->assertNotEmpty($all);
        $this->assertCount(14, $all);
        foreach ($all as $item) {
            $this->assertInstanceOf(
                '\Packlink\BusinessLogic\Controllers\DTO\ShippingMethodResponse',
                $item
            );
            $this->assertInternalType('array', $item->toArray());
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
        $shipment->pricePolicy = $first->pricePolicy;
        $shipment->isShipToAllCountries = true;
        $shipment->shippingCountries = array();

        $model = $this->controller->save($shipment);

        $this->assertNotNull($model);

        $this->assertEquals($shipment->id, $model->id);
        $this->assertEquals($shipment->name, $model->name);
        $this->assertEquals($shipment->showLogo, $model->showLogo);
        $this->assertEquals($shipment->pricePolicy, $model->pricePolicy);
    }

    public function testShippingMethodConfigurationToArray()
    {
        $instance = new ShippingMethodConfiguration();
        $instance->id = 12;
        $instance->name = 'First name test';
        $instance->showLogo = true;
        $instance->pricePolicy = ShippingMethod::PRICING_POLICY_FIXED_PRICE_BY_WEIGHT;
        $instance->fixedPriceByWeightPolicy[] = new FixedPricePolicy(0, 10, 12);

        $data = $instance->toArray();

        self::assertNotEmpty($data);
        self::assertEquals($instance->id, $data['id']);
        self::assertEquals($instance->name, $data['name']);
        self::assertEquals($instance->showLogo, $data['showLogo']);
        self::assertEquals($instance->pricePolicy, $data['pricePolicy']);
        self::assertCount(1, $data['fixedPriceByWeightPolicy']);
        self::assertEquals(0, $data['fixedPriceByWeightPolicy'][0]['from']);
        self::assertEquals(10, $data['fixedPriceByWeightPolicy'][0]['to']);
        self::assertEquals(12, $data['fixedPriceByWeightPolicy'][0]['amount']);

        $instance->fixedPriceByValuePolicy[] = new FixedPricePolicy(0, 100, 120);
        $instance->pricePolicy = ShippingMethod::PRICING_POLICY_FIXED_PRICE_BY_VALUE;
        $data = $instance->toArray();

        self::assertCount(1, $data['fixedPriceByValuePolicy']);
        self::assertEquals(0, $data['fixedPriceByValuePolicy'][0]['from']);
        self::assertEquals(100, $data['fixedPriceByValuePolicy'][0]['to']);
        self::assertEquals(120, $data['fixedPriceByValuePolicy'][0]['amount']);

        $instance->pricePolicy = ShippingMethod::PRICING_POLICY_PERCENT;
        $instance->percentPricePolicy = new PercentPricePolicy(false, 10);
        $data = $instance->toArray();

        self::assertEquals(false, $data['percentPricePolicy']['increase']);
        self::assertEquals(10, $data['percentPricePolicy']['amount']);
    }

    public function testShippingMethodResponseToArray()
    {
        $instance = new ShippingMethodResponse();
        $instance->id = 12;
        $instance->name = 'First name test';
        $instance->title = 'title';
        $instance->carrierName = 'carrier';
        $instance->deliveryDescription = 'description';
        $instance->parcelOrigin = 'pick-up';
        $instance->parcelDestination = 'drop-off';
        $instance->logoUrl = 'url';
        $instance->showLogo = false;
        $instance->selected = false;
        $instance->pricePolicy = ShippingMethod::PRICING_POLICY_FIXED_PRICE_BY_WEIGHT;
        $instance->fixedPriceByWeightPolicy[] = new FixedPricePolicy(0, 10, 12);

        $data = $instance->toArray();

        self::assertNotEmpty($data);
        self::assertEquals($instance->id, $data['id']);
        self::assertEquals($instance->name, $data['name']);
        self::assertEquals($instance->title, $data['title']);
        self::assertEquals($instance->carrierName, $data['carrierName']);
        self::assertEquals($instance->deliveryDescription, $data['deliveryDescription']);
        self::assertEquals($instance->parcelOrigin, $data['parcelOrigin']);
        self::assertEquals($instance->parcelDestination, $data['parcelDestination']);
        self::assertEquals($instance->logoUrl, $data['logoUrl']);
        self::assertEquals($instance->showLogo, $data['showLogo']);
        self::assertEquals($instance->selected, $data['selected']);
        self::assertEquals($instance->pricePolicy, $data['pricePolicy']);
        self::assertCount(1, $data['fixedPriceByWeightPolicy']);
        self::assertEquals(0, $data['fixedPriceByWeightPolicy'][0]['from']);
        self::assertEquals(10, $data['fixedPriceByWeightPolicy'][0]['to']);
        self::assertEquals(12, $data['fixedPriceByWeightPolicy'][0]['amount']);

        $instance->pricePolicy = ShippingMethod::PRICING_POLICY_FIXED_PRICE_BY_VALUE;
        $instance->fixedPriceByValuePolicy[] = new FixedPricePolicy(0, 100, 120);
        $data = $instance->toArray();
        self::assertCount(1, $data['fixedPriceByValuePolicy']);
        self::assertEquals(0, $data['fixedPriceByValuePolicy'][0]['from']);
        self::assertEquals(100, $data['fixedPriceByValuePolicy'][0]['to']);
        self::assertEquals(120, $data['fixedPriceByValuePolicy'][0]['amount']);

        $instance->pricePolicy = ShippingMethod::PRICING_POLICY_PERCENT;
        $instance->percentPricePolicy = new PercentPricePolicy(false, 10);
        $data = $instance->toArray();

        self::assertEquals(false, $data['percentPricePolicy']['increase']);
        self::assertEquals(10, $data['percentPricePolicy']['amount']);
    }

    public function testSaveNoShippingMethod()
    {
        $shipment = new ShippingMethodConfiguration();
        $shipment->id = 1235412;
        $shipment->name = 'First name test';
        $shipment->showLogo = true;
        $shipment->pricePolicy = 1;

        $this->assertNull($this->controller->save($shipment));
    }

    public function testSaveInvalidMissingProperty()
    {
        $shipment = new ShippingMethodConfiguration();
        $properties = array('id', 'name', 'showLogo', 'pricePolicy');
        $shipment->id = 1235412;
        $shipment->name = 'First name test';
        $shipment->showLogo = true;
        $shipment->pricePolicy = 1;

        foreach ($properties as $property) {
            $value = $shipment->$property;
            unset($shipment->$property);

            $this->assertNull($this->controller->save($shipment));

            $shipment->$property = $value;
        }
    }

    public function testSaveInvalidPropertyWrongType()
    {
        $shipment = new ShippingMethodConfiguration();
        $properties = array('id' => 'abc', 'name' => true, 'showLogo' => 12.5, 'pricePolicy' => 'abc');
        $shipment->id = 1235412;
        $shipment->name = 'First name test';
        $shipment->showLogo = true;
        $shipment->pricePolicy = 1;

        foreach ($properties as $property => $value) {
            $oldValue = $shipment->$property;
            $shipment->$property = $value;

            $this->assertNull($this->controller->save($shipment));

            $shipment->$property = $oldValue;
        }
    }

    public function testSaveInvalidMissingPricePolicy()
    {
        $this->importShippingMethods();
        $all = $this->controller->getAll();
        $first = $all[0];
        $shipment = new ShippingMethodConfiguration();
        $shipment->id = $first->id;
        $shipment->name = 'First name test';
        $shipment->showLogo = !$first->showLogo;

        $shipment->pricePolicy = ShippingMethod::PRICING_POLICY_PERCENT;
        $this->assertNull($this->controller->save($shipment));

        $shipment->pricePolicy = ShippingMethod::PRICING_POLICY_FIXED_PRICE_BY_WEIGHT;
        $this->assertNull($this->controller->save($shipment));

        $shipment->pricePolicy = ShippingMethod::PRICING_POLICY_FIXED_PRICE_BY_VALUE;
        $this->assertNull($this->controller->save($shipment));
    }

    public function testSaveCorrectPricePolicy()
    {
        $this->importShippingMethods();
        $all = $this->controller->getAll();
        $first = $all[0];
        $shipment = new ShippingMethodConfiguration();
        $shipment->id = $first->id;
        $shipment->name = 'First name test';
        $shipment->showLogo = !$first->showLogo;
        $shipment->isShipToAllCountries = true;
        $shipment->shippingCountries = array();

        $shipment->pricePolicy = ShippingMethod::PRICING_POLICY_PERCENT;
        $shipment->percentPricePolicy = new PercentPricePolicy(true, 0.1);
        $this->assertNotNull($this->controller->save($shipment));

        $shipment->pricePolicy = ShippingMethod::PRICING_POLICY_FIXED_PRICE_BY_WEIGHT;
        $shipment->fixedPriceByWeightPolicy = array();
        $shipment->fixedPriceByWeightPolicy[] = new FixedPricePolicy(0, 1, 1);
        $shipment->fixedPriceByWeightPolicy[] = new FixedPricePolicy(1, 2.5, 1.5);
        $this->assertNotNull($this->controller->save($shipment));

        $shipment->pricePolicy = ShippingMethod::PRICING_POLICY_FIXED_PRICE_BY_VALUE;
        $shipment->fixedPriceByValuePolicy = array();
        $shipment->fixedPriceByValuePolicy[] = new FixedPricePolicy(0, 1, 1);
        $shipment->fixedPriceByValuePolicy[] = new FixedPricePolicy(1, 2.5, 1.5);
        $this->assertNotNull($this->controller->save($shipment));
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
     * Calls other test to populate shipping methods storage.
     */
    private function importShippingMethods()
    {
        $test = new UpdateShippingServicesTaskTest();
        /** @noinspection PhpUnhandledExceptionInspection */
        $test->setUp();
        /** @noinspection PhpUnhandledExceptionInspection */
        $test->testExecuteAllNew();
    }
}
