<?php

namespace Packlink\BusinessLogic\Http\DTO\BFF;

use Logeecom\Infrastructure\Data\DataTransferObject;

/**
 * Class BffTrackingResponse.
 *
 * @package Packlink\BusinessLogic\Http\DTO\BFF
 */
class BffTrackingResponse extends DataTransferObject
{
    /**
     * Estimated delivery date.
     *
     * @var string
     */
    public $estimatedDeliveryDate;
    /**
     * Tracking status key.
     *
     * @var string
     */
    public $status;

    /**
     * Transforms DTO to its array format suitable for http client.
     *
     * @return array DTO in array format.
     */
    public function toArray()
    {
        return array(
            'estimatedDeliveryDate' => $this->estimatedDeliveryDate,
            'status' => $this->status,
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
        $parcels = static::getDataValue($raw, 'parcels', array());
        $firstParcel = isset($parcels[0]) ? $parcels[0] : array();
        $currentStatus = static::getDataValue($firstParcel, 'currentStatus', array());
        $label = static::getDataValue($currentStatus, 'label', array());

        $response = new static();
        $response->estimatedDeliveryDate = static::getDataValue($firstParcel, 'estimatedDeliveryDate');
        $response->status = static::getDataValue($label, 'key');

        return $response;
    }
}
