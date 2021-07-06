<?php

namespace Packlink\BusinessLogic\Controllers\DTO;

/**
 * Class ShippingMethodResponse.
 *
 * @package Packlink\BusinessLogic\Controllers\DTO
 */
class ShippingMethodResponse extends ShippingMethodConfiguration
{
    /**
     * Shipping method type (national/international).
     *
     * @var string
     */
    public $type;
    /**
     * Shipping carrier name.
     *
     * @var string
     */
    public $carrierName;
    /**
     * Description of delivery.
     *
     * @var string
     */
    public $deliveryDescription;
    /**
     * Shipping delivery type.
     *
     * @var string
     */
    public $deliveryType;
    /**
     * Parcel origin type.
     *
     * @var string
     */
    public $parcelOrigin;
    /**
     * Carrier logo URL.
     *
     * @var string
     */
    public $logoUrl;
    /**
     * Parcel destination type.
     *
     * @var string
     */
    public $parcelDestination;
    /**
     * Shipping method currency.
     * The value represents a currency code (ex. EUR, USD, GBP).
     *
     * @var string
     */
    public $currency;

    /**
     * Transforms DTO to its array format suitable for http client.
     *
     * @return array DTO in array format.
     */
    public function toArray()
    {
        return array_merge(
            parent::toArray(),
            array(
                'type' => $this->type,
                'carrierName' => $this->carrierName,
                'deliveryDescription' => $this->deliveryDescription,
                'deliveryType' => $this->deliveryType,
                'parcelOrigin' => $this->parcelOrigin,
                'parcelDestination' => $this->parcelDestination,
                'logoUrl' => $this->logoUrl,
                'currency' => $this->currency,
            )
        );
    }
}
