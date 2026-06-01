<?php

namespace Packlink\BusinessLogic\ShipmentDocument;

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Http\DTO\ShipmentLabel;
use Packlink\BusinessLogic\Order\OrderService;
use Packlink\BusinessLogic\OrderShipmentDetails\Models\OrderShipmentDetails;
use Packlink\BusinessLogic\OrderShipmentDetails\OrderShipmentDetailsService;
use Packlink\BusinessLogic\ShipmentDocument\DTO\ShipmentDocument;
use Packlink\BusinessLogic\ShipmentDocument\Interfaces\ShipmentDocumentServiceInterface;

/**
 * Class ShipmentDocumentService.
 *
 * Aggregates shipment documents (labels, future customs invoices) for the
 * presentation layer. Wraps existing `ShipmentLabel` data into the unified
 * `ShipmentDocument` representation without replacing it.
 *
 * @package Packlink\BusinessLogic\ShipmentDocument
 */
class ShipmentDocumentService implements ShipmentDocumentServiceInterface
{
    /**
     * @var OrderShipmentDetailsService
     */
    protected $orderShipmentDetailsService;
    /**
     * @var OrderService
     */
    protected $orderService;

    /**
     * ShipmentDocumentService constructor.
     */
    public function __construct()
    {
        $this->orderShipmentDetailsService = ServiceRegister::getService(OrderShipmentDetailsService::CLASS_NAME);
        $this->orderService = ServiceRegister::getService(OrderService::CLASS_NAME);
    }

    /**
     * Returns all available documents for the given order.
     *
     * @param string|int $orderId Order id in the integration system.
     *
     * @return ShipmentDocument[] List of documents (may be empty).
     */
    public function getDocumentsForOrder($orderId)
    {
        $details = $this->orderShipmentDetailsService->getDetailsByOrderId($orderId);
        if ($details === null) {
            return array();
        }

        return $this->collectShippingLabels($details);
    }

    /**
     * Marks a document as printed.
     *
     * @param string $shipmentReference Packlink shipment reference.
     * @param string $documentType One of ShipmentDocumentType constants.
     * @param string $link Document link, used to identify the specific document.
     *
     * @throws \Packlink\BusinessLogic\OrderShipmentDetails\Exceptions\OrderShipmentDetailsNotFound
     */
    public function markDocumentPrinted($shipmentReference, $documentType, $link)
    {
        if ($documentType === ShipmentDocumentType::SHIPPING_LABEL) {
            $this->orderShipmentDetailsService->markLabelPrinted($shipmentReference, $link);
        }
    }

    /**
     * Collects shipping labels for the given order shipment details and wraps them as documents.
     *
     * @param OrderShipmentDetails $details Order shipment details.
     *
     * @return ShipmentDocument[]
     */
    protected function collectShippingLabels(OrderShipmentDetails $details)
    {
        $labels = $details->getShipmentLabels();
        if (empty($labels) && $this->orderService->isReadyToFetchShipmentLabels($details->getStatus())) {
            $labels = $this->orderService->getShipmentLabels($details->getReference());
        }

        $documents = array();
        foreach ($labels as $label) {
            $documents[] = $this->labelToDocument($label);
        }

        return $documents;
    }

    /**
     * Wraps a `ShipmentLabel` into a `ShipmentDocument` with type SHIPPING_LABEL.
     *
     * @param ShipmentLabel $label Source shipment label.
     *
     * @return ShipmentDocument
     */
    protected function labelToDocument(ShipmentLabel $label)
    {
        return new ShipmentDocument(
            ShipmentDocumentType::SHIPPING_LABEL,
            $label->getLink(),
            $label->isPrinted(),
            ShipmentDocumentType::getLabel(ShipmentDocumentType::SHIPPING_LABEL)
        );
    }
}
