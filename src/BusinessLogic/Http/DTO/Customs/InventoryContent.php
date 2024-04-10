<?php

namespace Packlink\BusinessLogic\Http\DTO\Customs;

use Logeecom\Infrastructure\Data\DataTransferObject;

/**
 * Class InventoryContent
 *
 * @package Packlink\BusinessLogic\Http\DTO\Customs
 */
class InventoryContent extends DataTransferObject
{
    /**
     * @var string
     */
    public $tariffNumber;
    /**
     * @var string
     */
    public $description;
    /**
     * @var string
     */
    public $countryOfOrigin;
    /**
     * @var Money
     */
    public $itemValue;
    /**
     * @var float
     */
    public $itemWeight;
    /**
     * @var int
     */
    public $quantity;

    /**
     * @inheritDoc
     */
    public static function fromArray(array $data)
    {
        $result = new static();

        $result->tariffNumber = static::getDataValue($data, 'tariff_number');
        $result->description = static::getDataValue($data, 'description');
        $result->countryOfOrigin = static::getDataValue($data, 'country_of_origin');
        $result->itemValue = Money::fromArray(self::getDataValue($data, 'item_value'));
        $result->itemWeight = static::getDataValue($data, 'item_weight');
        $result->quantity = static::getDataValue($data, 'quantity');

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array(
            'tariff_number' => $this->tariffNumber,
            'description' => $this->description,
            'country_of_origin' => $this->countryOfOrigin,
            'item_value' => $this->itemValue->toArray(),
            'item_weight' => $this->itemWeight,
            'quantity' => $this->quantity,
        );
    }
}