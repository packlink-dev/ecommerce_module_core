<?php

namespace Logeecom\Tests\BusinessLogic\Order;

use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Order\TestOrderRepository;
use Logeecom\Tests\BusinessLogic\ShippingMethod\TestShopShippingMethodService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Http\DTO\ParcelInfo;
use Packlink\BusinessLogic\Http\DTO\ShippingServiceDetails;
use Packlink\BusinessLogic\Http\DTO\Warehouse;
use Packlink\BusinessLogic\Order\Interfaces\OrderRepository;
use Packlink\BusinessLogic\Order\OrderService;
use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\ShippingMethod\ShippingMethodService;

/**
 * Class OrderServiceTest.
 *
 * @package Logeecom\Tests\BusinessLogic\Order
 */
class OrderServiceTest extends BaseTestWithServices
{
    /**
     * Order service instance.
     *
     * @var OrderService
     */
    public $orderService;
    /**
     * @var TestOrderRepository
     */
    public $orderRepository;
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
    protected function setUp()
    {
        parent::setUp();
        $me = $this;

        $this->orderRepository = new TestOrderRepository();
        RepositoryRegistry::registerRepository(ShippingMethod::CLASS_NAME, MemoryRepository::getClassName());

        TestServiceRegister::registerService(
            OrderRepository::CLASS_NAME,
            function () use ($me) {
                return $me->orderRepository;
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

        $this->orderService = OrderService::getInstance();
        $this->shopConfig->setDefaultParcel(new ParcelInfo());
        $this->shopConfig->setDefaultWarehouse(new Warehouse());
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        OrderService::resetInstance();
        ShippingMethodService::resetInstance();

        parent::tearDown();
    }

    /**
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound
     */
    public function testPrepareDraft()
    {
        $method = $this->shippingMethodService->add($this->getShippingServiceDetails(123, 'PSP'));
        $this->orderRepository->getOrder('test', $method->getId(), 'IT');
        $draft = $this->orderService->prepareDraft('test');
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
        $draft = $this->orderService->prepareDraft('test');
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
        $this->orderRepository->getOrder('test', $method->getId(), 'DE');

        $this->orderService->prepareDraft('test');

        self::assertCount(1, $this->shopLogger->loggedMessages);
        self::assertEquals(
            'Invalid service method ' . $method->getId() . ' selected for order test because this method '
            . 'does not support order\'s destination country. Sending order without selected method.',
            $this->shopLogger->loggedMessages[0]->getMessage()
        );
    }

    /**
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound
     */
    public function testPrepareDraftGetCheapestService()
    {
        $this->shippingMethodService->add($this->getShippingServiceDetails(123, 'PSP', 3.14));
        $this->shippingMethodService->add($this->getShippingServiceDetails(234, 'PSP', 2.54));
        $method = $this->shippingMethodService->add($this->getShippingServiceDetails(456, 'PSP', 4.24));
        $this->orderRepository->getOrder('test', $method->getId(), 'IT');

        $draft = $this->orderService->prepareDraft('test');
        self::assertEquals(234, $draft->serviceId);
    }

    /**
     * @expectedException \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound
     */
    public function testPrepareDraftNoOrder()
    {
        /** @var TestOrderRepository $orderRepository */
        $orderRepository = TestServiceRegister::getService(OrderRepository::CLASS_NAME);
        $orderRepository->shouldThrowException(true);

        $this->orderService->prepareDraft('123');
    }

    /**
     *
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound
     */
    public function testSetReference()
    {
        $this->orderService->setReference('test', 'test_reference');

        /** @var TestOrderRepository $orderRepository */
        $orderRepository = TestServiceRegister::getService(OrderRepository::CLASS_NAME);
        $order = $orderRepository->getOrder('test');

        $this->assertEquals('test_reference', $order->getShipment()->getReferenceNumber());
    }

    /**
     * @expectedException \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound
     */
    public function testSetReferenceNoOrder()
    {
        /** @var TestOrderRepository $orderRepository */
        $orderRepository = TestServiceRegister::getService(OrderRepository::CLASS_NAME);
        $orderRepository->shouldThrowException(true);

        $this->orderService->setReference('123', '');
    }

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
