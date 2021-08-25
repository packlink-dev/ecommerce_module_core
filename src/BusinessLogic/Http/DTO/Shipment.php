<?php

namespace Packlink\BusinessLogic\Http\DTO;

use Logeecom\Infrastructure\Data\DataTransferObject;

/**
 * Class Shipment.
 *
 * @package Packlink\BusinessLogic\Http\DTO
 */
class Shipment extends DataTransferObject
{
    /**
     * Shipment reference unique identifier.
     *
     * @var string
     */
    public $reference;
    /**
     * Shipment custom reference.
     *
     * @var string
     */
    public $shipmentCustomReference;
    /**
     * Shipment status.
     *
     * @var string
     */
    public $status;
    /**
     * Packlink service Id.
     *
     * @var string
     */
    public $serviceId;
    /**
     * Shipment service name.
     *
     * @var string
     */
    public $service;
    /**
     * Shipment carrier name.
     *
     * @var string
     */
    public $carrier;
    /**
     * Shipment content.
     *
     * @var string
     */
    public $content;
    /**
     * Shipment price.
     *
     * @var float
     */
    public $price;
    /**
     * Shipment tracking codes.
     *
     * @var string[]
     */
    public $trackingCodes;
    /**
     * Carrier tracking URL.
     *
     * @var string
     */
    public $carrierTrackingUrl;
    /**
     * Order date.
     *
     * @var \DateTime
     */
    public $orderDate;
    /**
     * Currency ISO code.
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
        return array(
            'packlink_reference' => $this->reference,
            'shipment_custom_reference' => $this->shipmentCustomReference,
            'service' => $this->service,
            'content' => $this->content,
            'carrier' => $this->carrier,
            'state' => $this->status,
            'tracking_codes' => $this->trackingCodes,
            'price' => array(
                'base_price' => $this->price,
            ),
            'order_date' => $this->orderDate ? $this->orderDate->format('Y-m-d') : '',
            'tracking_url' => $this->carrierTrackingUrl,
            'service_id' => $this->serviceId,
            'currency' => $this->currency,
        );
    }

    /**
     * Transforms raw array data to its DTO.
     *
     * @param array $raw Raw array data.
     *
     * @return static Transformed DTO object.
     */
    public static function fromArray(array $raw)
    {
        $shipment = new static();
        $date = static::getDataValue($raw, 'order_date');
        if ($date) {
            $shipment->orderDate = \DateTime::createFromFormat('Y-m-d', $date);
        }

        $shipment->reference = static::getDataValue($raw, 'packlink_reference');
        $shipment->shipmentCustomReference = static::getDataValue($raw, 'shipment_custom_reference');
        $shipment->service = static::getDataValue($raw, 'service');
        $shipment->serviceId = static::getDataValue($raw, 'service_id');
        $shipment->content = static::getDataValue($raw, 'content');
        $shipment->carrier = static::getDataValue($raw, 'carrier');
        $shipment->status = static::getDataValue($raw, 'state');
        $shipment->trackingCodes = static::getDataValue($raw, 'trackings', array());
        $shipment->price = static::getDataValue($raw, 'price', null);
        $shipment->currency = static::getDataValue($raw, 'currency', 'EUR');
        if (is_array($shipment->price)) {
            $shipment->price = static::getDataValue($shipment->price, 'base_price');
        } else {
            $shipment->price = 0.0;
        }

        $shipment->carrierTrackingUrl = static::getDataValue($raw, 'tracking_url');

        return $shipment;
    }
}
