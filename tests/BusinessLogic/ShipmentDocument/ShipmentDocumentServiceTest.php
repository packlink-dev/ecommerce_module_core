<?php

namespace Logeecom\Tests\BusinessLogic\ShipmentDocument;

use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\TestRepositoryRegistry;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Http\DTO\ShipmentLabel;
use Packlink\BusinessLogic\Order\OrderService;
use Packlink\BusinessLogic\OrderShipmentDetails\Models\OrderShipmentDetails;
use Packlink\BusinessLogic\OrderShipmentDetails\OrderShipmentDetailsService;
use Packlink\BusinessLogic\ShipmentDocument\Interfaces\ShipmentDocumentServiceInterface;
use Packlink\BusinessLogic\ShipmentDocument\ShipmentDocumentService;
use Packlink\BusinessLogic\ShipmentDocument\ShipmentDocumentType;
use Packlink\BusinessLogic\ShippingMethod\Utility\ShipmentStatus;

/**
 * Class ShipmentDocumentServiceTest.
 *
 * @package Logeecom\Tests\BusinessLogic\ShipmentDocument
 */
class ShipmentDocumentServiceTest extends BaseTestWithServices
{
    /**
     * @var OrderShipmentDetailsService
     */
    public $orderShipmentDetailsService;
    /**
     * @var TestOrderService
     */
    public $orderService;
    /**
     * @var ShipmentDocumentService
     */
    public $service;

    /**
     * @before
     * @inheritdoc
     */
    protected function before()
    {
        parent::before();

        TestRepositoryRegistry::registerRepository(
            OrderShipmentDetails::CLASS_NAME,
            MemoryRepository::getClassName()
        );

        $me = $this;

        $this->orderShipmentDetailsService = OrderShipmentDetailsService::getInstance();
        TestServiceRegister::registerService(
            OrderShipmentDetailsService::CLASS_NAME,
            function () use ($me) {
                return $me->orderShipmentDetailsService;
            }
        );

        $this->orderService = new TestOrderService();
        TestServiceRegister::registerService(
            OrderService::CLASS_NAME,
            function () use ($me) {
                return $me->orderService;
            }
        );

        TestServiceRegister::registerService(
            ShipmentDocumentServiceInterface::CLASS_NAME,
            function () {
                return new ShipmentDocumentService();
            }
        );

        $this->service = ServiceRegister::getService(ShipmentDocumentServiceInterface::CLASS_NAME);
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
     * Tests that an order without persisted details yields no documents.
     */
    public function testGetDocumentsForOrderReturnsEmptyWhenNoDetails()
    {
        $documents = $this->service->getDocumentsForOrder('missing');

        $this->assertSame(array(), $documents);
        $this->assertSame(0, $this->orderService->getShipmentLabelsCalls);
    }

    /**
     * Tests that persisted labels are wrapped into ShipmentDocument entries
     * without falling back to the API.
     *
     * @throws \Packlink\BusinessLogic\OrderShipmentDetails\Exceptions\OrderShipmentDetailsNotFound
     */
    public function testGetDocumentsForOrderUsesPersistedLabels()
    {
        $this->orderShipmentDetailsService->setReference('order-1', 'ref-1');
        $this->orderShipmentDetailsService->setLabelsByReference(
            'ref-1',
            array(
                new ShipmentLabel('https://example.com/a.pdf', false),
                new ShipmentLabel('https://example.com/b.pdf', true),
            )
        );
        $this->orderService->isReady = true;

        $documents = $this->service->getDocumentsForOrder('order-1');

        $this->assertCount(2, $documents);
        $this->assertEquals(ShipmentDocumentType::SHIPPING_LABEL, $documents[0]->getType());
        $this->assertEquals('https://example.com/a.pdf', $documents[0]->getLink());
        $this->assertFalse($documents[0]->isPrinted());
        $this->assertEquals('Shipping label', $documents[0]->getName());

        $this->assertEquals('https://example.com/b.pdf', $documents[1]->getLink());
        $this->assertTrue($documents[1]->isPrinted());

        $this->assertSame(0, $this->orderService->getShipmentLabelsCalls, 'Persisted labels must not trigger API fallback.');
    }

    /**
     * Tests that the API fallback is invoked only when persisted labels are
     * empty and the status is one for which labels can be fetched.
     */
    public function testGetDocumentsForOrderFallsBackToApiWhenEmptyAndStatusReady()
    {
        $this->orderShipmentDetailsService->setReference('order-1', 'ref-1');
        $this->orderService->isReady = true;
        $this->orderService->labels = array(
            new ShipmentLabel('https://example.com/from-api.pdf'),
        );

        $documents = $this->service->getDocumentsForOrder('order-1');

        $this->assertCount(1, $documents);
        $this->assertEquals('https://example.com/from-api.pdf', $documents[0]->getLink());
        $this->assertSame(1, $this->orderService->getShipmentLabelsCalls);
    }

    /**
     * Tests that the API fallback is suppressed when the status is not ready.
     */
    public function testGetDocumentsForOrderDoesNotFetchWhenStatusNotReady()
    {
        $this->orderShipmentDetailsService->setReference('order-1', 'ref-1');
        $this->orderShipmentDetailsService->setShippingStatus('ref-1', ShipmentStatus::STATUS_PENDING);
        $this->orderService->isReady = false;
        $this->orderService->labels = array(
            new ShipmentLabel('https://example.com/should-not-appear.pdf'),
        );

        $documents = $this->service->getDocumentsForOrder('order-1');

        $this->assertSame(array(), $documents);
        $this->assertSame(0, $this->orderService->getShipmentLabelsCalls);
    }

    /**
     * Tests that markDocumentPrinted flips the matching label for SHIPPING_LABEL.
     *
     * @throws \Packlink\BusinessLogic\OrderShipmentDetails\Exceptions\OrderShipmentDetailsNotFound
     */
    public function testMarkDocumentPrintedFlipsLabelForShippingLabelType()
    {
        $this->orderShipmentDetailsService->setReference('order-1', 'ref-1');
        $this->orderShipmentDetailsService->setLabelsByReference(
            'ref-1',
            array(
                new ShipmentLabel('a'),
                new ShipmentLabel('b'),
            )
        );

        $this->service->markDocumentPrinted('ref-1', ShipmentDocumentType::SHIPPING_LABEL, 'b');

        $details = $this->orderShipmentDetailsService->getDetailsByReference('ref-1');
        $labels = $details->getShipmentLabels();
        $this->assertFalse($labels[0]->isPrinted());
        $this->assertTrue($labels[1]->isPrinted());
    }

    /**
     * Tests that markDocumentPrinted is a no-op for the customs invoice type
     * (forward-compat: no persistence target yet).
     *
     * @throws \Packlink\BusinessLogic\OrderShipmentDetails\Exceptions\OrderShipmentDetailsNotFound
     */
    public function testMarkDocumentPrintedNoOpForCustomsInvoice()
    {
        $this->orderShipmentDetailsService->setReference('order-1', 'ref-1');
        $this->orderShipmentDetailsService->setLabelsByReference(
            'ref-1',
            array(new ShipmentLabel('a'), new ShipmentLabel('b'))
        );

        $this->service->markDocumentPrinted('ref-1', ShipmentDocumentType::CUSTOMS_INVOICE, 'a');

        $details = $this->orderShipmentDetailsService->getDetailsByReference('ref-1');
        $labels = $details->getShipmentLabels();
        $this->assertFalse($labels[0]->isPrinted());
        $this->assertFalse($labels[1]->isPrinted());
    }

    /**
     * Tests that marking an unknown link is silently ignored, matching the
     * existing OrderShipmentDetailsService::markLabelPrinted semantics.
     *
     * @throws \Packlink\BusinessLogic\OrderShipmentDetails\Exceptions\OrderShipmentDetailsNotFound
     */
    public function testMarkDocumentPrintedNoOpForUnknownLink()
    {
        $this->orderShipmentDetailsService->setReference('order-1', 'ref-1');
        $this->orderShipmentDetailsService->setLabelsByReference(
            'ref-1',
            array(new ShipmentLabel('a'), new ShipmentLabel('b'))
        );

        $this->service->markDocumentPrinted('ref-1', ShipmentDocumentType::SHIPPING_LABEL, 'nonexistent');

        $details = $this->orderShipmentDetailsService->getDetailsByReference('ref-1');
        $labels = $details->getShipmentLabels();
        $this->assertFalse($labels[0]->isPrinted());
        $this->assertFalse($labels[1]->isPrinted());
    }

    /**
     * Tests that marking a document for an unregistered shipment reference
     * propagates the OrderShipmentDetailsNotFound exception from the
     * underlying service.
     *
     * @expectedException \Packlink\BusinessLogic\OrderShipmentDetails\Exceptions\OrderShipmentDetailsNotFound
     */
    public function testMarkDocumentPrintedThrowsWhenReferenceUnknown()
    {
        $this->service->markDocumentPrinted('missing', ShipmentDocumentType::SHIPPING_LABEL, 'a');
    }
}
