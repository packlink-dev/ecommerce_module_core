<?php

namespace Packlink\BusinessLogic\Http\DTO\BFF;

use Logeecom\Infrastructure\Data\DataTransferObject;

/**
 * Class BffPostsaleResponse.
 *
 * @package Packlink\BusinessLogic\Http\DTO\BFF
 */
class BffPostsaleResponse extends DataTransferObject
{
    /**
     * Public tracking URL.
     *
     * @var string
     */
    public $publicTrackingUrl;
    /**
     * Order reference.
     *
     * @var string
     */
    public $orderReference;
    /**
     * Whether the shipment is a drop-off shipment.
     *
     * @var bool
     */
    public $isDropOff;
    /**
     * Carrier icon URL.
     *
     * @var string
     */
    public $carrierIcon;
    /**
     * Service name.
     *
     * @var string
     */
    public $serviceName;
    /**
     * Shipment status label.
     *
     * @var string
     */
    public $shipmentStatusLabel;

    /**
     * Transforms DTO to its array format suitable for http client.
     *
     * @return array DTO in array format.
     */
    public function toArray()
    {
        return array(
            'publicTrackingUrl' => $this->publicTrackingUrl,
            'orderReference' => $this->orderReference,
            'isDropOff' => $this->isDropOff,
            'carrierIcon' => $this->carrierIcon,
            'serviceName' => $this->serviceName,
            'shipmentStatusLabel' => $this->shipmentStatusLabel,
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
        $shipmentData = static::getDataValue($raw, 'shipmentData', array());
        $details = static::getDataValue($shipmentData, 'shipmentDetails', array());
        $service = static::getDataValue($shipmentData, 'service', array());
        $status = static::getDataValue($shipmentData, 'shipmentStatus', array());

        $response = new static();
        $response->publicTrackingUrl = static::getDataValue($details, 'publicTrackingUrl');
        $response->orderReference = static::getDataValue($details, 'orderReference');
        $response->isDropOff = static::getDataValue($details, 'isDropOff', false);
        $response->carrierIcon = static::getDataValue($service, 'carrierIcon');
        $response->serviceName = static::getDataValue($service, 'name');
        $response->shipmentStatusLabel = static::getDataValue($status, 'label');

        return $response;
    }
}
