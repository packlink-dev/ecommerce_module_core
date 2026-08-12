<?php

namespace Packlink\BusinessLogic\Http\DTO\DDP;

use Logeecom\Infrastructure\Data\DataTransferObject;

/**
 * Class DdpProductCost
 *
 * @package Packlink\BusinessLogic\Http\DTO\DDP
 */
class DdpProductCost extends DataTransferObject
{
    /**
     * @var float
     */
    public $basePrice;
    /**
     * @var float
     */
    public $taxPrice;
    /**
     * @var float
     */
    public $totalPrice;
    /**
     * @var string
     */
    public $currency;
    /**
     * @var bool
     */
    public $isEnabled;
    /**
     * @var bool
     */
    public $isSelected;

    /**
     * @inheritDoc
     */
    public static function fromArray(array $data)
    {
        $result = new static();

        $result->basePrice = (float)static::getDataValue($data, 'base_price', 0.0);
        $result->taxPrice = (float)static::getDataValue($data, 'tax_price', 0.0);
        $result->totalPrice = (float)static::getDataValue($data, 'total_price', 0.0);
        $result->currency = (string)static::getDataValue($data, 'currency');
        $result->isEnabled = (bool)static::getDataValue($data, 'is_enabled', false);
        $result->isSelected = (bool)static::getDataValue($data, 'is_selected', false);

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array(
            'base_price' => $this->basePrice,
            'tax_price' => $this->taxPrice,
            'total_price' => $this->totalPrice,
            'currency' => $this->currency,
            'is_enabled' => $this->isEnabled,
            'is_selected' => $this->isSelected,
        );
    }
}
