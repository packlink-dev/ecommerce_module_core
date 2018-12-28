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
    public function __construct($increase = true, $amount = 0)
    {
        $this->increase = $increase;
        $this->amount = $amount;
    }
}
