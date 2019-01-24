<?php

namespace Logeecom\Tests\BusinessLogic\Order;

use Logeecom\Tests\BusinessLogic\Common\TestComponents\Order\TestOrderRepository;
use Logeecom\Tests\Infrastructure\Common\BaseTestWithServices;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Http\DTO\Draft;
use Packlink\BusinessLogic\Http\DTO\ParcelInfo;
use Packlink\BusinessLogic\Http\DTO\ShipmentReference;
use Packlink\BusinessLogic\Http\DTO\Warehouse;
use Packlink\BusinessLogic\Order\Interfaces\OrderRepository;
use Packlink\BusinessLogic\Order\OrderService;

/**
 * Class OrderServiceTest
 * @package Logeecom\Tests\BusinessLogic\Order
 */
class OrderServiceTest extends BaseTestWithServices
{
    /**
     * Order service instance.
     *
     * @var OrderService
     */
    private $orderService;

    public function testPrepareDraft()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $draft = $this->orderService->prepareDraft('test');
        $this->assertInstanceOf(Draft::CLASS_NAME, $draft);
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

    public function testSetReference()
    {
        $reference = new ShipmentReference();
        $reference->reference = 'test_reference';
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->orderService->setReference('test', $reference);

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

        $this->orderService->setReference('123', new ShipmentReference());
    }

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $orderRepository = new TestOrderRepository();

        TestServiceRegister::registerService(
            OrderRepository::CLASS_NAME,
            function () use ($orderRepository) {
                return $orderRepository;
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
        parent::tearDown();
    }
}
