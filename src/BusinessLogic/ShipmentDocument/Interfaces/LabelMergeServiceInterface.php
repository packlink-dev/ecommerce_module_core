<?php

namespace Packlink\BusinessLogic\ShipmentDocument\Interfaces;

/**
 * Interface LabelMergeServiceInterface.
 *
 * Merges shipping-label PDFs for multiple shipments into a single document,
 * used for bulk print/download.
 *
 * @package Packlink\BusinessLogic\ShipmentDocument\Interfaces
 */
interface LabelMergeServiceInterface
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * Merges the shipping-label PDFs for the given shipment references into a
     * single PDF and returns the raw bytes. Page order follows the array order.
     *
     * @param string[] $shipmentReferences Packlink shipment reference IDs.
     *
     * @return string Raw merged PDF bytes.
     */
    public function getMergedLabelsPdf(array $shipmentReferences): string;
}
