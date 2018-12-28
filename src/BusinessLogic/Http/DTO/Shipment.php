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
     * Shipment weight.
     *
     * @var float
     */
    public $weight;
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
     * Shipment canceled flag.
     *
     * @var bool
     */
    public $canceled;
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
            'reference' => $this->reference,
            'shipment_custom_reference' => $this->shipmentCustomReference,
            'status' => $this->status,
            'service' => $this->service,
            'content' => $this->content,
            'carrier' => $this->carrier,
            'weight' => $this->weight,
            'tracking_codes' => $this->trackingCodes,
            'price' => $this->price,
            'canceled' => $this->canceled,
            'orderDate' => $this->orderDate->format('Y-m-d'),
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
        $shipment->orderDate = \DateTime::createFromFormat("Y-m-d|", $raw['orderDate']);
        $shipment->reference = $raw['reference'];
        $shipment->shipmentCustomReference = $raw['shipment_custom_reference'];
        $shipment->status = $raw['status'];
        $shipment->service = $raw['service'];
        $shipment->content = $raw['content'];
        $shipment->carrier = $raw['carrier'];
        $shipment->weight = $raw['weight'];
        $shipment->trackingCodes = $raw['tracking_codes'];
        $shipment->price = $raw['price'];
        $shipment->canceled = $raw['canceled'];

        return $shipment;
    }
}
