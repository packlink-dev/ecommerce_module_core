<?php

namespace Packlink\BusinessLogic\ShippingMethod\Models;

/**
 * Class PercentPricePolicy.
 * Used for ShippingMethod when percent price policy from Packlink price is applied.
 *
 * @package Packlink\BusinessLogic\ShippingMethod\Models
 */
class PercentPricePolicy
{
    /**
     * Indicates whether to increase or decrease price for specified percent amount.
     *
     * @var bool
     */
    public $increase;
    /**
     * Amount in percents for increase/decrease.
     *
     * @var float
     */
    public $amount;

    /**
     * PercentPricePolicy constructor.
     *
     * @param bool $increase Indicates whether to increase or decrease price for specified percent amount.
     * @param int $amount Amount in percents for increase/decrease.
     */
    public function __construct($increase, $amount)
    {
        $this->increase = $increase;
        $this->amount = $amount;
    }

    /**
     * Transforms raw array data to this entity instance.
     *
     * @param array $data Raw array data.
     *
     * @return static Transformed entity object.
     */
    public static function fromArray($data)
    {
        return new static((bool)$data['increase'], $data['amount']);
    }

    /**
     * Transforms entity to its array format representation.
     *
     * @return array Entity in array format.
     */
    public function toArray()
    {
        return array(
            'increase' => $this->increase,
            'amount' => $this->amount,
        );
    }
}
