<?php

namespace Packlink\BusinessLogic\Http\DTO\DDP;

use Logeecom\Infrastructure\Data\DataTransferObject;
use Packlink\BusinessLogic\Http\DTO\Package;

/**
 * Class ShipmentProductsRequestItem. The single shipment entry of a /pro/shipments/products request.
 *
 * This payload is the one the live API accepts, verified 2026-07-30. It is NOT a literal transcription
 * of Packlink's written guide, whose example JSON is wrong in several places -- see the platform docs
 * (`api-guide-vs-reality.md`). Two deliberate omissions:
 *
 *  - `packlink_reference` is optional. At checkout no shipment exists yet, and the endpoint prices
 *    correctly without it. Do not send an empty string.
 *  - `contentValue_currency` is not a field of this endpoint. Sending it was part of what made every
 *    earlier request fail with HTTP 500.
 *
 * @package Packlink\BusinessLogic\Http\DTO\DDP
 */
class ShipmentProductsRequestItem extends DataTransferObject
{
    /**
     * @var string
     */
    public $serviceId;
    /**
     * @var float
     */
    public $contentValue;
    /**
     * @var string
     */
    public $fromCountry;
    /**
     * @var string
     */
    public $fromZip;
    /**
     * @var string
     */
    public $fromCity;
    /**
     * @var string
     */
    public $toCountry;
    /**
     * @var string
     */
    public $toZip;
    /**
     * @var string
     */
    public $toCity;
    /**
     * @var Package[]
     */
    public $packages = array();
    /**
     * @var string
     */
    public $customsInvoiceId;

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        $packages = array();
        foreach ($this->packages as $package) {
            $packages[] = $package->toArray();
        }

        return array(
            'service_id' => $this->serviceId,
            'contentvalue' => round($this->contentValue, 2),
            'insurance' => array(
                'insurance_selected' => false,
            ),
            'content_second_hand' => false,
            'from' => array(
                'city' => $this->fromCity,
                'country' => $this->fromCountry,
                'zip_code' => $this->fromZip,
            ),
            'to' => array(
                'city' => $this->toCity,
                'country' => $this->toCountry,
                'zip_code' => $this->toZip,
            ),
            'packages' => $packages,
            'source' => 'PRO',
            'proof_of_delivery' => false,
            'adult_signature' => false,
            'additional_handling' => false,
            // Required for summary totals to include DDP. The components are read directly either way.
            'selected_products' => array(
                'ddp' => array(
                    'is_selected' => true,
                ),
            ),
            'customs' => array(
                'customs_invoice_id' => $this->customsInvoiceId,
            ),
        );
    }
}
