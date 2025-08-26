<?php

namespace Logeecom\Tests\BusinessLogic\Order;

use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Tests\BusinessLogic\CashOnDelivery\TestCashOnDeliveryService;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\TestWarehouse;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Order\TestShopOrderService;
use Logeecom\Tests\BusinessLogic\ShippingMethod\TestShopShippingMethodService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\CashOnDelivery\Interfaces\CashOnDeliveryServiceInterface;
use Packlink\BusinessLogic\CashOnDelivery\Model\Account;
use Packlink\BusinessLogic\CashOnDelivery\Model\CashOnDelivery;
use Packlink\BusinessLogic\Http\DTO\ParcelInfo;
use Packlink\BusinessLogic\Http\DTO\ShippingServiceDetails;
use Packlink\BusinessLogic\Order\Exceptions\EmptyOrderException;
use Packlink\BusinessLogic\Order\Interfaces\ShopOrderService;
use Packlink\BusinessLogic\Order\OrderService;
use Packlink\BusinessLogic\OrderShipmentDetails\Models\OrderShipmentDetails;
use Packlink\BusinessLogic\OrderShipmentDetails\OrderShipmentDetailsService;
use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\ShippingMethod\PackageTransformer;
use Packlink\BusinessLogic\ShippingMethod\ShippingMethodService;

/**
 * Class OrderServiceTest.
 *
 * @package Logeecom\Tests\BusinessLogic\Order
 */
class OrderServiceTest extends BaseTestWithServices
{
    /** @var TestCashOnDeliveryService $cashOnDeliveryService*/

    private $cashOnDeliveryService;

    /**
     * Order service instance.
     *
     * @var OrderService
     */
    public $orderService;
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
     * @inheritdoc
     */
    protected function before()
    {
        parent::before();

        $me = $this;

        RepositoryRegistry::registerRepository(ShippingMethod::CLASS_NAME, MemoryRepository::getClassName());
        RepositoryRegistry::registerRepository(OrderShipmentDetails::CLASS_NAME, MemoryRepository::getClassName());

        TestServiceRegister::registerService(
            OrderShipmentDetailsService::CLASS_NAME,
            function () {
                return OrderShipmentDetailsService::getInstance();
            }
        );

        $this->shopOrderService = new TestShopOrderService();
        TestServiceRegister::registerService(
            ShopOrderService::CLASS_NAME,
            function () use ($me) {
                return $me->shopOrderService;
            }
        );

        TestServiceRegister::registerService(
            PackageTransformer::CLASS_NAME,
            function () {
                return PackageTransformer::getInstance();
            }
        );

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
        /** @noinspection PhpUnhandledExceptionInspection */
        RepositoryRegistry::registerRepository(CashOnDelivery::CLASS_NAME, MemoryRepository::getClassName());

        $this->cashOnDeliveryService = new TestCashOnDeliveryService();
        ServiceRegister::registerService(
            CashOnDeliveryServiceInterface::CLASS_NAME,
            function () use ($me) {
                return $me->cashOnDeliveryService;
            }
        );

        $this->orderService = OrderService::getInstance();
        $this->shopConfig->setDefaultParcel(ParcelInfo::defaultParcel());
        $this->shopConfig->setDefaultWarehouse(new TestWarehouse());
    }

    /**
     * @after
     * @inheritdoc
     */
    protected function after()
    {
        OrderService::resetInstance();
        ShippingMethodService::resetInstance();

        parent::after();
    }

    /**
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound
     */
    public function testPrepareDraft()
    {
        $method = $this->shippingMethodService->add($this->getShippingServiceDetails(123, 'PSP'));
        $order = $this->shopOrderService->getOrder('test', $method->getId(), 'IT');
        $draft = $this->orderService->prepareDraft($order);
        $this->assertInstanceOf('Packlink\BusinessLogic\Http\DTO\Draft', $draft);
        self::assertNotEmpty($draft->content);
        self::assertNotEmpty($draft->packages);
        self::assertNotEmpty($draft->to);
        self::assertEquals(123, $draft->serviceId);
    }

    /**
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound
     */
    public function testPrepareDraftNoShippingMethod()
    {
        $draft = $this->orderService->prepareDraft($this->shopOrderService->getOrder('test', 'IT'));
        $this->assertInstanceOf('Packlink\BusinessLogic\Http\DTO\Draft', $draft);
        self::assertNotEmpty($draft->content);
        self::assertNotEmpty($draft->packages);
        self::assertNotEmpty($draft->to);
        self::assertEmpty($draft->serviceId);
    }

    /**
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound
     */
    public function testPrepareDraftWrongDestinationCountry()
    {
        $method = $this->shippingMethodService->add($this->getShippingServiceDetails(123, 'PSP'));
        $order = $this->shopOrderService->getOrder('test', $method->getId(), 'DE');

        $this->orderService->prepareDraft($order);

        $logMessages = $this->shopLogger->loggedMessages;
        self::assertCount(2, $logMessages);
        self::assertEquals('Missing required search parameter(s).', $logMessages[0]->getMessage());
        self::assertEquals(
            'Invalid service method ' . $method->getId() . ' selected for order test because this method '
            . 'does not support order\'s destination country. Sending order without selected method.',
            $logMessages[1]->getMessage()
        );
    }

    /**
     * @throws EmptyOrderException
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound
     */
    public function testPrepareDraftShippingReference()
    {
        $draft = $this->orderService->prepareDraft($this->shopOrderService->getOrder('test', 'IT'));
        self::assertEquals($draft->shipmentCustomReference, 'testOrderNumber');
    }

    /**
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound
     */
    public function testPrepareDraftGetCheapestService()
    {
        $this->shippingMethodService->add($this->getShippingServiceDetails(123, 'PSP', 3.14));
        $this->shippingMethodService->add($this->getShippingServiceDetails(234, 'PSP', 2.54));
        $method = $this->shippingMethodService->add($this->getShippingServiceDetails(456, 'PSP', 4.24));
        $order = $this->shopOrderService->getOrder('test', $method->getId(), 'IT');

        $draft = $this->orderService->prepareDraft($order);
        self::assertEquals(234, $draft->serviceId);
    }

    /**
     * @return void
     * @throws EmptyOrderException
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound
     */
    public function testPrepareDraftNoOrder()
    {
        /** @var TestShopOrderService $orderRepository */
        $orderRepository = TestServiceRegister::getService(ShopOrderService::CLASS_NAME);
        $orderRepository->shouldThrowOrderNotFoundException(true);

        $exThrown = null;
        try {
            $order = $this->shopOrderService->getOrder('test', 'IT');
            $this->orderService->prepareDraft($order);
        } catch (\Packlink\BusinessLogic\Order\Exceptions\OrderNotFound $ex) {
            $exThrown = $ex;
        }

        $this->assertNotNull($exThrown);
    }

    /**
     * @return void
     * @throws EmptyOrderException
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound
     */
    public function testPrepareDraftWithCashOnDelivery()
    {
        $shippingServiceDetails = $this->getShippingServiceDetails(123, 'PSP', 3.14);

        $method = $this->shippingMethodService->add($shippingServiceDetails);

        $order = $this->shopOrderService->getOrder('test', $method->getId(), 'IT');

        $entity = new CashOnDelivery();
        $entity->setSystemId($this->shopConfig->getCurrentSystemId());
        $entity->setEnabled(true);

        $account = new Account();
        $account->setCashOnDeliveryFee(2);
        $account->setIban('E1');
        $account->setAccountHolderName('Test Account');

        $entity->setAccount($account);

        $this->cashOnDeliveryService->setEntity($entity);

        $draft = $this->orderService->prepareDraft($order);

        $codArray = $draft->cashOnDelivery->toArray();

        self::assertEquals('E1', $codArray['iban']);
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
            )
        );

        $details->departureCountry = 'IT';
        $details->destinationCountry = $toCountry;
        $details->national = $details->departureCountry === $toCountry;

        return $details;
    }
}
