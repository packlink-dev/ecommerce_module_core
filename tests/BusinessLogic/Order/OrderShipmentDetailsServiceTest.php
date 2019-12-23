<?php

namespace Logeecom\Tests\BusinessLogic\Order;

use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\TestRepositoryRegistry;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\OrderShipmentDetails\Models\OrderShipmentDetails;
use Packlink\BusinessLogic\OrderShipmentDetails\OrderShipmentDetailsService;

/**
 * Class OrderShipmentDetailsServiceTest.
 *
 * @package Logeecom\Tests\BusinessLogic\Order
 */
class OrderShipmentDetailsServiceTest extends BaseTestWithServices
{
    /**
     * @var OrderShipmentDetailsService
     */
    public $orderShipmentDetailsService;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        TestRepositoryRegistry::registerRepository(OrderShipmentDetails::CLASS_NAME, MemoryRepository::getClassName());

        $me = $this;
        $me->orderShipmentDetailsService = OrderShipmentDetailsService::getInstance();
        TestServiceRegister::registerService(
            OrderShipmentDetailsService::CLASS_NAME,
            function () use ($me) {
                return $me->orderShipmentDetailsService;
            }
        );
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        OrderShipmentDetailsService::resetInstance();

        parent::tearDown();
    }

    /**
     * Tests creating shipment details object.
     */
    public function testCreate()
    {
        $this->orderShipmentDetailsService->setReference('test', 'test_reference');

        $shipmentDetailsByRef = $this->orderShipmentDetailsService->getDetailsByReference('test_reference');
        $shipmentDetailsById = $this->orderShipmentDetailsService->getDetailsByReference('test_reference');

        $this->assertEquals('test_reference', $shipmentDetailsByRef->getReference());
        $this->assertEquals('test', $shipmentDetailsByRef->getOrderId());
        $this->assertEquals($shipmentDetailsById->getId(), $shipmentDetailsByRef->getId());
    }
}
