<?php

namespace Packlink\BusinessLogic\Http\DTO\Shipment;

use Logeecom\Infrastructure\Data\DataTransferObject;

/**
 * Class AdditionalData
 *
 * @package Packlink\BusinessLogic\Http\DTO\Shipment
 */
class AdditionalData extends DataTransferObject
{
    /**
     * @var string
     */
    public $orderId;

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array(
            'order_id' => $this->orderId,
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
        $additionalData = new static();

        $additionalData->orderId = static::getDataValue($raw, 'order_id');

        return $additionalData;
    }
}
