<?php

namespace Packlink\BusinessLogic\ShipmentDocument;

use Packlink\BusinessLogic\Language\Translator;

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
     * Returns a localised, human-readable label for a document type.
     *
     * Looks up `shipmentDocumentType.<key>` via the integration-core
     * Translator (so platforms get the right locale via
     * Configuration::setUICountryCode). Falls back to hardcoded English when
     * no localised entry exists, so legacy installs without the new keys
     * keep working.
     *
     * @param string $type
     *
     * @return string
     */
    public static function getLabel($type)
    {
        $localised = self::tryLocalise($type);
        if ($localised !== null) {
            return $localised;
        }

        $defaults = array(
            self::SHIPPING_LABEL  => 'Shipping label',
            self::CUSTOMS_INVOICE => 'Customs invoice',
        );

        return isset($defaults[$type]) ? $defaults[$type] : $type;
    }

    /**
     * Tries to fetch a localised label via the Translator. Returns null when
     * no entry exists (Translator returned the key unchanged).
     *
     * @param string $type
     *
     * @return string|null
     */
    private static function tryLocalise($type)
    {
        $keys = array(
            self::SHIPPING_LABEL  => 'shipmentDocumentType.shippingLabel',
            self::CUSTOMS_INVOICE => 'shipmentDocumentType.customsInvoice',
        );

        if (!isset($keys[$type])) {
            return null;
        }

        $key = $keys[$type];
        $translated = Translator::translate($key);

        return $translated !== $key ? $translated : null;
    }
}