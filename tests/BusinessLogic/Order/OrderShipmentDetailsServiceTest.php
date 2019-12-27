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

    /**
     * Test setting labels.
     */
    public function testSetLabels()
    {
        $this->orderShipmentDetailsService->setReference('test', 'test_reference');
        $this->orderShipmentDetailsService->setLabelsByReference('test_reference', array('label1', 'label2'));

        $shipmentDetailsByRef = $this->orderShipmentDetailsService->getDetailsByReference('test_reference');

        $labels = $shipmentDetailsByRef->getShipmentLabels();
        $this->assertCount(2, $labels, 'Shipment labels must be set.');
        $this->assertEquals('label1', $labels[0]->getLink(), 'A link for shipment label must be set');
        $this->assertEquals('label2', $labels[1]->getLink(), 'A link for shipment label must be set');
        $this->assertFalse($labels[0]->isPrinted());
    }

    /**
     * @expectedException \Packlink\BusinessLogic\OrderShipmentDetails\Exceptions\OrderShipmentDetailsNotFound
     */
    public function testSetLabelsUnknownReference()
    {
        $this->orderShipmentDetailsService->setReference('test', 'test_reference');
        $this->orderShipmentDetailsService->setLabelsByReference('some reference', array('label1', 'label2'));

        $this->fail('Exception must be thrown if shipment reference is not found.');
    }

    /**
     * Test marking labels printed.
     */
    public function testMarkLabelPrinted()
    {
        $this->orderShipmentDetailsService->setReference('test', 'test_reference');
        $this->orderShipmentDetailsService->setLabelsByReference('test_reference', array('label1', 'label2'));
        $this->orderShipmentDetailsService->markLabelPrinted('test_reference', 'label2');

        $shipmentDetailsByRef = $this->orderShipmentDetailsService->getDetailsByReference('test_reference');

        $labels = $shipmentDetailsByRef->getShipmentLabels();
        $this->assertCount(2, $labels, 'Shipment labels must be set.');
        $this->assertFalse($labels[0]->isPrinted());
        $this->assertTrue($labels[1]->isPrinted());
    }
}
