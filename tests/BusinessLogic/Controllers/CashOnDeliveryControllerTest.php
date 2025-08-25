<?php

namespace Controllers;

use Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Tests\BusinessLogic\CashOnDelivery\TestCashOnDeliveryService;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Order\TestShopOrderService;
use Logeecom\Tests\BusinessLogic\ShippingMethod\TestShopShippingMethodService;
use Logeecom\Tests\BusinessLogic\Subscription\TestSubscriptionService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\CashOnDelivery\Interfaces\CashOnDeliveryServiceInterface;
use Packlink\BusinessLogic\CashOnDelivery\Model\Account;
use Packlink\BusinessLogic\CashOnDelivery\Model\CashOnDelivery;
use Packlink\BusinessLogic\Controllers\CashOnDeliveryController;
use Packlink\BusinessLogic\Http\DTO\ShippingServiceDetails;
use Packlink\BusinessLogic\Order\Interfaces\ShopOrderService;
use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\ShippingMethod\PackageTransformer;
use Packlink\BusinessLogic\ShippingMethod\ShippingMethodService;
use Packlink\BusinessLogic\Subscription\Interfaces\SubscriptionServiceInterface;
use Packlink\BusinessLogic\Warehouse\Warehouse;

class CashOnDeliveryControllerTest extends BaseTestWithServices
{
    /** @var RepositoryInterface */
    private $repository;


    /** @var TestCashOnDeliveryService $cashOnDeliveryService*/

    private $cashOnDeliveryService;


    /** @var TestSubscriptionService $subscriptionService*/
    private $subscriptionService;

    /** @var CashOnDeliveryController */
    private $controller;

    /**
     * @var TestShopOrderService
     */
    public $shopOrderService;

    /**
     * @var ShippingMethodService
     */
    public $shippingMethodService;

    /**
     * @var TestShopShippingMethodService
     */
    public $testShopShippingMethodService;

    /**
     * @before
     * @inheritDoc
     * @throws RepositoryNotRegisteredException
     */
    public function before()
    {
        parent::before();

        $me = $this;

        /** @noinspection PhpUnhandledExceptionInspection */
        RepositoryRegistry::registerRepository(CashOnDelivery::CLASS_NAME, MemoryRepository::getClassName());

        $this->subscriptionService = new TestSubscriptionService();
        ServiceRegister::registerService(
            SubscriptionServiceInterface::CLASS_NAME,
            function () use ($me) {
                return $me->subscriptionService;
            }
        );

        $this->cashOnDeliveryService = new TestCashOnDeliveryService();
        ServiceRegister::registerService(
            CashOnDeliveryServiceInterface::CLASS_NAME,
            function () use ($me) {
                return $me->cashOnDeliveryService;
            }
        );

        $this->repository = RepositoryRegistry::getRepository(CashOnDelivery::CLASS_NAME);

        $this->controller = new CashOnDeliveryController();

        $this->shopOrderService = new TestShopOrderService();
        TestServiceRegister::registerService(
            ShopOrderService::CLASS_NAME,
            function () use ($me) {
                return $me->shopOrderService;
            }
        );

        RepositoryRegistry::registerRepository(ShippingMethod::CLASS_NAME, MemoryRepository::getClassName());

        $this->testShopShippingMethodService = new TestShopShippingMethodService();
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

        TestServiceRegister::registerService(
            PackageTransformer::CLASS_NAME,
            function () {
                return PackageTransformer::getInstance();
            }
        );
    }

    /**
     * @throws QueryFilterInvalidParamException
     */
    public function testGetCODNoneExists()
    {
        $dto = $this->controller->getCashOnDeliveryConfiguration();

        $this->assertNull($dto);
    }

    public function testGetCashOnDelivery()
    {
        $entity = new CashOnDelivery();
        $entity->setSystemId($this->shopConfig->getCurrentSystemId());
        $entity->setEnabled(true);

        $this->cashOnDeliveryService->setEntity($entity);

        $dto = $this->controller->getCashOnDeliveryConfiguration();

        $this->assertTrue($dto->enabled);
    }

    public function testSaveConfigCreatesEntity()
    {
        $rawData = array(
            'systemId' => $this->shopConfig->getCurrentSystemId(),
            'enabled' => true,
            'active' => true,
            'account' => array('iban' => 'RS35123456789012345678'),
        );

        $id = $this->controller->saveConfig($rawData);

        $this->assertNotNull($id);

        $entity = $this->cashOnDeliveryService->getCashOnDeliveryConfig();
        $this->assertTrue($entity->isEnabled());
        $this->assertTrue($entity->isActive());
    }

    /**
     * @throws QueryFilterInvalidParamException
     */
    public function testGetAndUpdateSubscriptionCreatesEntityIfNoneExistsAndPlusSubscription()
    {
        $this->subscriptionService->setValue(true);

        $result = $this->controller->getAndUpdateSubscription();

        $this->assertTrue($result);
    }

    /**
     * @throws QueryFilterInvalidParamException
     */
    public function testGetAndUpdateSubscriptionDisablesWhenNoSubscription()
    {
        $entity = new CashOnDelivery();
        $entity->setSystemId($this->shopConfig->getCurrentSystemId());
        $entity->setEnabled(true);
        $entity->setActive(true);
        $entity->setAccount(new Account());

        $this->cashOnDeliveryService->setEntity($entity);
        $this->subscriptionService->setValue(false);

        $result = $this->controller->getAndUpdateSubscription();

        $this->assertFalse($result);
        $this->assertFalse($this->cashOnDeliveryService->getCashOnDeliveryConfig()->isEnabled());
    }

    /**
     * @throws QueryFilterInvalidParamException
     */
    public function testGetAndUpdateSubscriptionEnablesWhenHasSubscription()
    {
        $entity = new CashOnDelivery();
        $entity->setSystemId($this->shopConfig->getCurrentSystemId());
        $entity->setEnabled(false);
        $entity->setActive(true);
        $entity->setAccount(new Account());

        $this->cashOnDeliveryService->setEntity($entity);
        $this->subscriptionService->setValue(true);

        $result = $this->controller->getAndUpdateSubscription();

        $this->assertTrue($result);
        $this->assertTrue($this->cashOnDeliveryService->getCashOnDeliveryConfig()->isEnabled());
    }

    /**
     * @throws QueryFilterInvalidParamException
     */
    public function testCalculateFeeReturnsAccountFee()
    {
        $method = $this->shippingMethodService->add($this->getShippingServiceDetails(123, 'PSP'));
        $order = $this->shopOrderService->getOrder('test', $method->getId(), 'IT');

        $account = new Account();
        $account->setCashOnDeliveryFee(12.5);

        $entity = new CashOnDelivery();
        $entity->setSystemId($this->shopConfig->getCurrentSystemId());
        $entity->setAccount($account);

        $this->cashOnDeliveryService->setEntity($entity);

        $result = $this->controller->calculateFee($order);

        $this->assertEquals(12.5, $result);
    }

    /**
     * @throws QueryFilterInvalidParamException
     */
    public function testCalculateFeeUsesShippingServicePercentage()
    {
        $method = $this->shippingMethodService->add($this->getShippingServiceDetails(123, 'PSP'));
        $order = $this->shopOrderService->getOrder('test', $method->getId(), 'IT');
        $order->setTotalPrice(20);

        $this->shopConfig->setDefaultWarehouse($this->createWarehouse());

        $fee = $this->controller->calculateFee($order);

        $this->assertEquals(round($order->getTotalPrice() * (2.75 / 100), 2), $fee);
    }

    /**
     * @throws QueryFilterInvalidParamException
     */
    public function testCalculateFeeUsesMinFee()
    {
        $method = $this->shippingMethodService->add($this->getShippingServiceDetails(123, 'PSP'));
        $order = $this->shopOrderService->getOrder('test', $method->getId(), 'IT');
        $order->setTotalPrice(10);

        $this->shopConfig->setDefaultWarehouse($this->createWarehouse());

        $fee = $this->controller->calculateFee($order);

        $this->assertEquals(0.5, $fee);
    }

    /**
     * Creates a default Warehouse instance for testing.
     *
     * @return Warehouse
     */
    private function createWarehouse(
    ) {
        $warehouse = new Warehouse();
        $warehouse->country = 'IT';
        $warehouse->postalCode = '10000';
        $warehouse->city = 'Test City';
        $warehouse->address = 'Test Address';

        $warehouse->alias = 'Default Warehouse';
        $warehouse->name = 'Test Name';
        $warehouse->surname = 'Test Surname';
        $warehouse->phone = '+381601234567';
        $warehouse->email = 'test@example.com';
        $warehouse->default = true;

        return $warehouse;
    }


    /**
     * @param $id
     * @param $carrierName
     * @param float $basePrice
     * @param string $toCountry
     *
     * @return \Packlink\BusinessLogic\Http\DTO\ShippingServiceDetails
     */
    private function getShippingServiceDetails($id, $carrierName, $basePrice = 10.76, $toCountry = 'IT')
    {
        $details = ShippingServiceDetails::fromArray(
            array(
                'id' => $id,
                'carrier_name' => $carrierName,
                'service_name' => 'test service',
                'currency' => 'EUR',
                'country' => $toCountry,
                'dropoff' => false,
                'delivery_to_parcelshop' => false,
                'category' => 'express',
                'transit_time' => '3 DAYS',
                'transit_hours' => 72,
                'first_estimated_delivery_date' => '2019-01-05',
                'price' => array(
                    'tax_price' => 3,
                    'base_price' => $basePrice,
                    'total_price' => $basePrice + 3,
                ),
                'cash_on_delivery' => array(
                    'apply_percentage_cash_on_delivery' => '2.75',
                    'offered' => false,
                    'max_cash_on_delivery' => '0.5',
                    'min_cash_on_delivery' => '0.00',
                ),
            )
        );

        $details->departureCountry = 'IT';
        $details->destinationCountry = $toCountry;
        $details->national = $details->departureCountry === $toCountry;

        return $details;
    }
}