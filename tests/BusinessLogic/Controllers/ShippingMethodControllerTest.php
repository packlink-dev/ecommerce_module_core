<?php

namespace Logeecom\Tests\BusinessLogic\Controllers;

use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Tests\BusinessLogic\ShippingMethod\TestShopShippingMethodService;
use Logeecom\Tests\Common\BaseTestWithServices;
use Logeecom\Tests\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Common\TestComponents\TestHttpClient;
use Logeecom\Tests\Common\TestServiceRegister;
use Packlink\BusinessLogic\Controllers\DTO\FixedPricePolicy;
use Packlink\BusinessLogic\Controllers\ShippingMethodController;
use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;
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
                \Packlink\BusinessLogic\Controllers\DTO\ShippingMethodConfiguration::CLASS_NAME,
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
        $shipment = new \Packlink\BusinessLogic\Controllers\DTO\ShippingMethodConfiguration();
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

    public function testSaveNoShippingMethod()
    {
        $shipment = new \Packlink\BusinessLogic\Controllers\DTO\ShippingMethodConfiguration();
        $shipment->id = 1235412;
        $shipment->name = 'First name test';
        $shipment->showLogo = true;
        $shipment->pricePolicy = 1;

        $this->assertFalse($this->controller->save($shipment));
    }

    public function testSaveInvalidMissingProperty()
    {
        $shipment = new \Packlink\BusinessLogic\Controllers\DTO\ShippingMethodConfiguration();
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
        $shipment = new \Packlink\BusinessLogic\Controllers\DTO\ShippingMethodConfiguration();
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
        $shipment = new \Packlink\BusinessLogic\Controllers\DTO\ShippingMethodConfiguration();
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
        $shipment = new \Packlink\BusinessLogic\Controllers\DTO\ShippingMethodConfiguration();
        $shipment->id = $first->id;
        $shipment->name = 'First name test';
        $shipment->showLogo = !$first->showLogo;

        $shipment->pricePolicy = ShippingMethod::PRICING_POLICY_PERCENT;
        $shipment->percentPricePolicy = new FixedPricePolicy();
        $this->assertFalse($this->controller->save($shipment));
    }

    public function testSaveCorrectPricePolicy()
    {
        $this->importShippingMethods();
        $all = $this->controller->getAll();
        $first = $all[0];
        $shipment = new \Packlink\BusinessLogic\Controllers\DTO\ShippingMethodConfiguration();
        $shipment->id = $first->id;
        $shipment->name = 'First name test';
        $shipment->showLogo = !$first->showLogo;

        $shipment->pricePolicy = ShippingMethod::PRICING_POLICY_PERCENT;
        $shipment->percentPricePolicy = new \Packlink\BusinessLogic\Controllers\DTO\PercentPricePolicy();
        $shipment->percentPricePolicy->increase = true;
        $shipment->percentPricePolicy->amount = 0.1;
        $this->assertNotFalse($this->controller->save($shipment));

        $shipment->pricePolicy = ShippingMethod::PRICING_POLICY_FIXED;
        $shipment->fixedPricePolicy = array();

        $policy = new FixedPricePolicy();
        $policy->from = 0;
        $policy->to = 1;
        $policy->amount = 1;
        $shipment->fixedPricePolicy[] = $policy;

        $policy = new FixedPricePolicy();
        $policy->from = 1;
        $policy->to = 2.5;
        $policy->amount = 1.5;
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
