<?php

namespace Packlink\BusinessLogic\Http\DTO;

/**
 * Class DraftResponse
 * @package Packlink\BusinessLogic\Http\DTO
 */
class ShipmentReference extends BaseDto
{
    /**
     * Packlink Shipment Reference.
     *
     * @var string
     */
    public $reference;

    /**
     * Transforms raw array data to its DTO.
     *
     * @param array $raw Raw array data.
     *
     * @return static Transformed DTO object.
     */
    public static function fromArray(array $raw)
    {
        $instance = new static();
        $instance->reference = static::getValue($raw, 'reference');

        return $instance;
    }

    /**
     * Transforms DTO to its array format suitable for http client.
     *
     * @return array DTO in array format.
     */
    public function toArray()
    {
        return array(
            'reference' => $this->reference,
        );
    }
}
