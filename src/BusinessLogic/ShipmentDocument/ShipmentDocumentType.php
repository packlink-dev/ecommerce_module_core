<?php

namespace Packlink\BusinessLogic\ShipmentDocument;

/**
 * Class ShipmentDocumentType.
 *
 * @package Packlink\BusinessLogic\ShipmentDocument
 */
class ShipmentDocumentType
{
    /**
     * Shipping label document type.
     */
    const SHIPPING_LABEL = 'shipping_label';
    /**
     * Customs invoice document type.
     */
    const CUSTOMS_INVOICE = 'customs_invoice';

    /**
     * Returns all known document types.
     *
     * @return string[]
     */
    public static function getAll()
    {
        return array(
            self::SHIPPING_LABEL,
            self::CUSTOMS_INVOICE,
        );
    }

    /**
     * Returns a human-readable label for a document type.
     *
     * @param string $type
     *
     * @return string
     */
    public static function getLabel($type)
    {
        $labels = array(
            self::SHIPPING_LABEL => 'Shipping label',
            self::CUSTOMS_INVOICE => 'Customs invoice',
        );

        return isset($labels[$type]) ? $labels[$type] : $type;
    }
}
