<?php

namespace Packlink\BusinessLogic\ShipmentDocument\Interfaces;

use Packlink\BusinessLogic\ShipmentDocument\DTO\ShipmentDocument;

/**
 * Interface ShipmentDocumentServiceInterface.
 *
 * Aggregates all available documents for an order (shipping labels,
 * customs invoices, ...) and tracks per-document print state.
 *
 * @package Packlink\BusinessLogic\ShipmentDocument\Interfaces
 */
interface ShipmentDocumentServiceInterface
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * Returns all available documents for the given order.
     *
     * @param string|int $orderId Order id in the integration system.
     *
     * @return ShipmentDocument[] List of documents (may be empty).
     */
    public function getDocumentsForOrder($orderId);

    /**
     * Marks a document as printed.
     *
     * @param string $shipmentReference Packlink shipment reference.
     * @param string $documentType One of \Packlink\BusinessLogic\ShipmentDocument\ShipmentDocumentType constants.
     * @param string $link Document link, used to identify the specific document.
     *
     * @throws \Packlink\BusinessLogic\OrderShipmentDetails\Exceptions\OrderShipmentDetailsNotFound
     */
    public function markDocumentPrinted($shipmentReference, $documentType, $link);
}
