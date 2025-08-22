<?php

namespace Packlink\BusinessLogic\ShippingMethod\Models;

use Packlink\BusinessLogic\Http\DTO\ShippingServiceDetails;

/**
 * Class ShippingMethodCost. Represents shipping cost for default parcel for shipment
 * from departure country to destination country.
 *
 * @package Packlink\BusinessLogic\ShippingMethod\Models
 */
class ShippingService
{
    /**
     * Packlink service id.
     *
     * @var int
     */
    public $serviceId;
    /**
     * Shipping service name.
     *
     * @var string
     */
    public $serviceName;
    /**
     * Departure country ISO-2 code.
     *
     * @var string
     */
    public $departureCountry;
    /**
     * Destination country ISO-2 code.
     *
     * @var string
     */
    public $destinationCountry;
    /**
     * Total price with tax.
     *
     * @var float
     */
    public $totalPrice;
    /**
     * Tax price.
     *
     * @var float
     */
    public $taxPrice;
    /**
     * Base price.
     *
     * @var float
     */
    public $basePrice;

    /**
     * @var string $category
     */
    public $category;

    /**
     * @var CashOnDeliveryConfig|null $cashOnDeliveryConfig
     */
    public $cashOnDeliveryConfig;

    /**
     * ShippingService constructor.
     *
     * @param string $serviceId Packlink service id.
     * @param string $serviceName Service name.
     * @param string $departureCountry Departure country ISO-2 code.
     * @param string $destinationCountry Destination country ISO-2 code.
     * @param float $totalPrice Total price with tax.
     * @param float $basePrice Base price.
     * @param float $taxPrice Tax price.
     * @param CashOnDeliveryConfig|null $cashOnDeliveryConfig
     */
    public function __construct(
        $serviceId = '',
        $serviceName = '',
        $departureCountry = '',
        $destinationCountry = '',
        $totalPrice = 0.0,
        $basePrice = 0.0,
        $taxPrice = 0.0,
        $category = '',
        $cashOnDeliveryConfig = null
    ) {
        $this->serviceId = $serviceId;
        $this->serviceName = $serviceName;
        $this->departureCountry = $departureCountry;
        $this->destinationCountry = $destinationCountry;
        $this->totalPrice = $totalPrice;
        $this->basePrice = $basePrice;
        $this->taxPrice = $taxPrice;
        $this->category = $category;
        $this->cashOnDeliveryConfig = $cashOnDeliveryConfig;
    }

    /**
     * Transforms raw array data to this entity instance.
     *
     * @param array $data Raw array data.
     *
     * @return static Transformed entity object.
     */
    public static function fromArray($data)
    {
        return new static(
            $data['serviceId'],
            $data['serviceName'],
            $data['departure'],
            $data['destination'],
            $data['totalPrice'],
            $data['basePrice'],
            $data['taxPrice'],
            isset($data['category']) ? $data['category'] : '',
            isset($data['cash_on_delivery'])
                ? CashOnDeliveryConfig::fromArray($data['cash_on_delivery'])
                : null
        );
    }

    /**
     * Creates new instance from data from @see ShippingServiceDetails instance.
     *
     * @param ShippingServiceDetails $shippingServiceDetails Service details.
     *
     * @return ShippingService New instance.
     */
    public static function fromServiceDetails(ShippingServiceDetails $shippingServiceDetails)
    {
        return new static(
            $shippingServiceDetails->id,
            $shippingServiceDetails->serviceName,
            $shippingServiceDetails->departureCountry,
            $shippingServiceDetails->destinationCountry,
            $shippingServiceDetails->totalPrice,
            $shippingServiceDetails->basePrice,
            $shippingServiceDetails->taxPrice,
            $shippingServiceDetails->category,
            !empty($shippingServiceDetails->cashOnDelivery)
                ? CashOnDeliveryConfig::fromArray($shippingServiceDetails->cashOnDelivery)
                : null
        );
    }

    /**
     * Transforms entity to its array format representation.
     *
     * @return array Entity in array format.
     */
    public function toArray()
    {
        return array(
            'serviceId' => $this->serviceId,
            'serviceName' => $this->serviceName,
            'departure' => $this->departureCountry,
            'destination' => $this->destinationCountry,
            'totalPrice' => $this->totalPrice,
            'basePrice' => $this->basePrice,
            'taxPrice' => $this->taxPrice,
            'category' => $this->category,
            'cash_on_delivery' => $this->cashOnDeliveryConfig
                ? $this->cashOnDeliveryConfig->toArray()
                : null
        );
    }
}
