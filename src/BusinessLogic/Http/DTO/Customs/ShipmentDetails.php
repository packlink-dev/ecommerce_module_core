<?php

namespace Packlink\BusinessLogic\Http\DTO\Customs;

use Logeecom\Infrastructure\Data\DataTransferObject;

/**
 * Class ShipmentDetails
 *
 * @package Packlink\BusinessLogic\Http\DTO\Customs
 */
class ShipmentDetails extends DataTransferObject
{
    /**
     * @var int
     */
    public $parcelsSize;
    /**
     * @var float
     */
    public $parcelsWeight;
    /**
     * @var Cost
     */
    public $cost;

    /**
     * @inheritDoc
     */
    public static function fromArray(array $raw)
    {
        $instance = new self();

        $instance->parcelsSize = static::getDataValue($raw, 'parcels_size', 0);
        $instance->parcelsWeight = static::getDataValue($raw, 'parcels_weight', 0);
        $instance->cost = Cost::fromArray(static::getDataValue($raw, 'cost', array()));

        return $instance;
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array(
            'parcels_size' => $this->parcelsSize,
            'parcels_weight' => $this->parcelsWeight,
            'cost' => $this->cost->toArray(),
        );
    }
}