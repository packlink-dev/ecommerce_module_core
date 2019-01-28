<?php

namespace Packlink\BusinessLogic\Http\DTO;

/**
 * Class Shipment
 * @package Packlink\BusinessLogic\Http\DTO
 */
class Shipment extends BaseDto
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
            'order_date' => $this->orderDate->format('Y-m-d'),
            'tracking_url' => $this->carrierTrackingUrl,
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
        $shipment->orderDate = \DateTime::createFromFormat('Y-m-d', $raw['order_date']);
        $shipment->reference = $raw['packlink_reference'];
        $shipment->shipmentCustomReference = $raw['shipment_custom_reference'];
        $shipment->service = $raw['service'];
        $shipment->content = $raw['content'];
        $shipment->carrier = $raw['carrier'];
        $shipment->status = $raw['state'];
        $shipment->trackingCodes = $raw['trackings'];
        $shipment->price = $raw['price']['base_price'];
        $shipment->carrierTrackingUrl = $raw['tracking_url'];

        return $shipment;
    }
}
