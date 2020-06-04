<?php

namespace Packlink\BusinessLogic\Http\DTO;

use Logeecom\Infrastructure\Data\DataTransferObject;

/**
 * Class ShippingServiceDetails holds information about delivery details for specific shipping service
 * and for specific departure and destination.
 *
 * @package Packlink\BusinessLogic\Http\DTO
 */
class ShippingServiceDetails extends DataTransferObject
{
    /**
     * Service Id.
     *
     * @var string
     */
    public $id;
    /**
     * Carrier name.
     *
     * @var string
     */
    public $carrierName;
    /**
     * Service name.
     *
     * @var string
     */
    public $serviceName;
    /**
     * Currency for this service.
     *
     * @var string
     */
    public $currency;
    /**
     * Country for this service. 2 letter country code.
     *
     * @var string
     */
    public $country;
    /**
     * Departure country for delivery details. 2 letter country code.
     *
     * @var string
     */
    public $departureCountry;
    /**
     * Destination country for delivery details. 2 letter country code.
     *
     * @var string
     */
    public $destinationCountry;
    /**
     * Indicates if this is national shipping service.
     *
     * @var bool
     */
    public $national;
    /**
     * Indicates whether service requires departure drop-off.
     *
     * @var bool
     */
    public $departureDropOff;
    /**
     * Indicates whether service requires destination drop-off.
     *
     * @var bool
     */
    public $destinationDropOff;
    /**
     * Indicates whether shipment labels are required.
     *
     * @var bool
     */
    public $labelsRequired;
    /**
     * Category for the service.
     *
     * @var string
     */
    public $category;
    /**
     * Express delivery support.
     *
     * @var bool
     */
    public $expressDelivery;
    /**
     * Transit time in days as string "X DAYS".
     *
     * @var string
     */
    public $transitTime;
    /**
     * Transit time in hours.
     *
     * @var int
     */
    public $transitHours;
    /**
     * First estimated delivery date.
     *
     * @var \DateTime
     */
    public $firstEstimatedDeliveryDate;
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
     * Array with information about service. Each item is an array in following format:
     *   [
     *      "text" => "Description",
     *      "icon" => "printer"
     *   ]
     * @var array
     */
    public $serviceInfo;
    /**
     * Available delivery dates. Array key is date and value is string in the following format: "[09:00 , 18:00]".
     *
     * @var array
     */
    public $availableDates;

    /**
     * @inheritdoc
     */
    public function toArray()
    {
        return array(
            'id' => $this->id,
            'carrier_name' => $this->carrierName,
            'service_name' => $this->serviceName,
            'currency' => $this->currency,
            'country' => $this->country,
            'dropoff' => $this->departureDropOff,
            'delivery_to_parcelshop' => $this->destinationDropOff,
            'labels_required' => $this->labelsRequired,
            'category' => $this->category,
            'transit_time' => $this->transitTime,
            'transit_hours' => $this->transitHours,
            'first_estimated_delivery_date' => $this->firstEstimatedDeliveryDate->format('YYYY/MM/DD'),
            'price' => array(
                'total_price' => $this->totalPrice,
                'tax_price' => $this->taxPrice,
                'base_price' => $this->basePrice,
            ),
            'service_info' => $this->serviceInfo,
            'available_dates' => $this->availableDates,
        );
    }

    /**
     * @inheritdoc
     */
    public static function fromArray(array $raw)
    {
        $instance = new static();

        $instance->id = self::getDataValue($raw, 'id');
        $instance->carrierName = self::getDataValue($raw, 'carrier_name');
        $instance->serviceName = self::getDataValue($raw, 'service_name');
        $instance->currency = self::getDataValue($raw, 'currency');
        $instance->country = self::getDataValue($raw, 'country');
        $instance->departureDropOff = self::getDataValue($raw, 'dropoff', false);
        $instance->destinationDropOff = self::getDataValue($raw, 'delivery_to_parcelshop', false);
        $instance->labelsRequired = self::getDataValue($raw, 'labels_required', false);
        $instance->category = self::getDataValue($raw, 'category');
        $instance->expressDelivery = $instance->category === 'express';
        $instance->transitTime = self::getDataValue($raw, 'transit_time');
        $instance->transitHours = (int)self::getDataValue($raw, 'transit_hours', 0);
        $instance->firstEstimatedDeliveryDate = \DateTime::createFromFormat(
            'YYYY/MM/DD',
            self::getDataValue($raw, 'first_estimated_delivery_date', '1970/01/01')
        );
        /** @var array $prices */
        $prices = self::getDataValue($raw, 'price', array());
        if (!empty($prices)) {
            $instance->totalPrice = self::getDataValue($prices, 'total_price', 0);
            $instance->taxPrice = self::getDataValue($prices, 'tax_price', 0);
            $instance->basePrice = self::getDataValue($prices, 'base_price', 0);
        }

        $instance->serviceInfo = self::getDataValue($raw, 'service_info', array());
        $instance->availableDates = self::getDataValue($raw, 'available_dates', array());
        $instance->national = self::getDataValue($raw, 'national', null);

        return $instance;
    }
}
