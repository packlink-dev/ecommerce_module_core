<?php

namespace Logeecom\Tests\BusinessLogic\Order;

use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\TestWarehouse;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Order\TestShopOrderService;
use Logeecom\Tests\BusinessLogic\ShippingMethod\TestShopShippingMethodService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
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
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

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

        $this->orderService = OrderService::getInstance();
        $this->shopConfig->setDefaultParcel(ParcelInfo::defaultParcel());
        $this->shopConfig->setDefaultWarehouse(new TestWarehouse());
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
        $this->shopOrderService->getOrder('test', $method->getId(), 'IT');
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
        $this->shopOrderService->getOrder('test', $method->getId(), 'DE');

        $this->orderService->prepareDraft('test');

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
        $draft = $this->orderService->prepareDraft('test');
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
        $this->shopOrderService->getOrder('test', $method->getId(), 'IT');

        $draft = $this->orderService->prepareDraft('test');
        self::assertEquals(234, $draft->serviceId);
    }

    /**
     * @expectedException \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound
     */
    public function testPrepareDraftNoOrder()
    {
        /** @var TestShopOrderService $orderRepository */
        $orderRepository = TestServiceRegister::getService(ShopOrderService::CLASS_NAME);
        $orderRepository->shouldThrowOrderNotFoundException(true);

        $this->orderService->prepareDraft('123');
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
