<?php

namespace Logeecom\Tests\BusinessLogic\Order;

use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\TestRepositoryRegistry;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Http\DTO\ShipmentLabel;
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
     * @before
     * @inheritdoc
     */
    protected function before()
    {
        parent::before();

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
     * @after
     * @inheritdoc
     */
    protected function after()
    {
        OrderShipmentDetailsService::resetInstance();

        parent::after();
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
     *
     * @throws \Packlink\BusinessLogic\OrderShipmentDetails\Exceptions\OrderShipmentDetailsNotFound
     */
    public function testSetLabels()
    {
        $this->orderShipmentDetailsService->setReference('test', 'test_reference');
        $this->orderShipmentDetailsService->setLabelsByReference(
            'test_reference',
            array(new ShipmentLabel('label1'), new ShipmentLabel('label2'))
        );

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
        $this->orderShipmentDetailsService->setLabelsByReference(
            'some reference',
            array(new ShipmentLabel('label1'), new ShipmentLabel('label2'))
        );

        $this->fail('Exception must be thrown if shipment reference is not found.');
    }

    /**
     * Test marking labels printed.
     *
     * @throws \Packlink\BusinessLogic\OrderShipmentDetails\Exceptions\OrderShipmentDetailsNotFound
     */
    public function testMarkLabelPrinted()
    {
        $this->orderShipmentDetailsService->setReference('test', 'test_reference');
        $this->orderShipmentDetailsService->setLabelsByReference(
            'test_reference',
            array(new ShipmentLabel('label1'), new ShipmentLabel('label2'))
        );
        $this->orderShipmentDetailsService->markLabelPrinted('test_reference', 'label2');

        $shipmentDetailsByRef = $this->orderShipmentDetailsService->getDetailsByReference('test_reference');

        $labels = $shipmentDetailsByRef->getShipmentLabels();
        $this->assertCount(2, $labels, 'Shipment labels must be set.');
        $this->assertFalse($labels[0]->isPrinted());
        $this->assertTrue($labels[1]->isPrinted());
    }
}
