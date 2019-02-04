<?php

namespace Logeecom\Tests\BusinessLogic\Controllers;

use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\BusinessLogic\ShippingMethod\TestShopShippingMethodService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestHttpClient;
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
        $this->assertCount(21, $all);
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

        $model = $this->controller->save($shipment);

        $this->assertNotFalse($model);

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
        $instance->pricePolicy = ShippingMethod::PRICING_POLICY_FIXED;
        $instance->fixedPricePolicy[] = new FixedPricePolicy(0, 10, 12);

        $data = $instance->toArray();

        self::assertNotEmpty($data);
        self::assertEquals($instance->id, $data['id']);
        self::assertEquals($instance->name, $data['name']);
        self::assertEquals($instance->showLogo, $data['showLogo']);
        self::assertEquals($instance->pricePolicy, $data['pricePolicy']);
        self::assertCount(1, $data['fixedPricePolicy']);
        self::assertEquals(0, $data['fixedPricePolicy'][0]['from']);
        self::assertEquals(10, $data['fixedPricePolicy'][0]['to']);
        self::assertEquals(12, $data['fixedPricePolicy'][0]['amount']);

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
        $instance->pricePolicy = ShippingMethod::PRICING_POLICY_FIXED;
        $instance->fixedPricePolicy[] = new FixedPricePolicy(0, 10, 12);

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
        self::assertCount(1, $data['fixedPricePolicy']);
        self::assertEquals(0, $data['fixedPricePolicy'][0]['from']);
        self::assertEquals(10, $data['fixedPricePolicy'][0]['to']);
        self::assertEquals(12, $data['fixedPricePolicy'][0]['amount']);

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

        $this->assertFalse($this->controller->save($shipment));
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

            $this->assertFalse($this->controller->save($shipment));

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

            $this->assertFalse($this->controller->save($shipment));

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
        $this->assertFalse($this->controller->save($shipment));

        $shipment->pricePolicy = ShippingMethod::PRICING_POLICY_FIXED;
        $this->assertFalse($this->controller->save($shipment));
    }

    public function testSaveInvalidWrongPricePolicyModel()
    {
        $this->importShippingMethods();
        $all = $this->controller->getAll();
        $first = $all[0];
        $shipment = new ShippingMethodConfiguration();
        $shipment->id = $first->id;
        $shipment->name = 'First name test';
        $shipment->showLogo = !$first->showLogo;

        $shipment->pricePolicy = ShippingMethod::PRICING_POLICY_PERCENT;
        $shipment->percentPricePolicy = new FixedPricePolicy(-1, 0, 0);
        $this->assertFalse($this->controller->save($shipment));
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

        $shipment->pricePolicy = ShippingMethod::PRICING_POLICY_PERCENT;
        $shipment->percentPricePolicy = new PercentPricePolicy(true, 0.1);
        $this->assertNotFalse($this->controller->save($shipment));

        $shipment->pricePolicy = ShippingMethod::PRICING_POLICY_FIXED;
        $shipment->fixedPricePolicy = array();

        $policy = new FixedPricePolicy(0, 1, 1);
        $shipment->fixedPricePolicy[] = $policy;

        $policy = new FixedPricePolicy(1, 2.5, 1.5);
        $shipment->fixedPricePolicy[] = $policy;
        $this->assertNotFalse($this->controller->save($shipment));
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
