<?php

namespace Packlink\BusinessLogic\Http\DTO;

use Logeecom\Infrastructure\Data\DataTransferObject;

/**
 * Class PlanPrice holds the shipping price of one service on one Packlink PRO subscription plan,
 * as returned in the price simulation of the services endpoints.
 *
 * @package Packlink\BusinessLogic\Http\DTO
 */
class PlanPrice extends DataTransferObject
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Free plan tier.
     */
    const TIER_FREE = 'FREE';
    /**
     * Plus plan tier.
     */
    const TIER_PLUS = 'PLUS';
    /**
     * Premium plan tier.
     */
    const TIER_PREMIUM = 'PREMIUM';
    /**
     * Plan code, for example "plus_es".
     *
     * @var string
     */
    public $planCode;
    /**
     * Shipping price including taxes, or null when Packlink returned no price.
     *
     * @var float|null
     */
    public $totalPrice;
    /**
     * Shipping price before taxes, or null when Packlink returned no price.
     *
     * @var float|null
     */
    public $basePrice;
    /**
     * Tax amount, or null when Packlink returned no price.
     *
     * @var float|null
     */
    public $taxPrice;
    /**
     * Currency of the amounts, or null when Packlink returned no price.
     *
     * @var string|null
     */
    public $currency;

    /**
     * Returns the plan tiers from the lowest to the highest.
     *
     * @return string[]
     */
    public static function getTiers()
    {
        return array(self::TIER_FREE, self::TIER_PLUS, self::TIER_PREMIUM);
    }

    /**
     * Maps a plan code to its tier by the part before the first underscore ("plus_es" is PLUS).
     *
     * @param string|null $planCode Plan code.
     *
     * @return string|null Tier, or null when the code does not name a known tier.
     */
    public static function tierFromPlanCode($planCode)
    {
        if (!is_string($planCode) || $planCode === '') {
            return null;
        }

        $parts = explode('_', $planCode, 2);
        $tier = strtoupper($parts[0]);

        return in_array($tier, self::getTiers(), true) ? $tier : null;
    }

    /**
     * @inheritdoc
     */
    public function toArray()
    {
        return array(
            'plan_code' => $this->planCode,
            'price' => array(
                'total_price' => $this->totalPrice,
                'base_price' => $this->basePrice,
                'tax_price' => $this->taxPrice,
                'currency' => $this->currency,
            ),
        );
    }

    /**
     * @inheritdoc
     */
    public static function fromArray(array $raw)
    {
        $instance = new static();

        $instance->planCode = self::getDataValue($raw, 'plan_code', null);
        $price = self::getDataValue($raw, 'price', null);
        if (is_array($price)) {
            $instance->totalPrice = self::getDataValue($price, 'total_price', null);
            $instance->basePrice = self::getDataValue($price, 'base_price', null);
            $instance->taxPrice = self::getDataValue($price, 'tax_price', null);
            $instance->currency = self::getDataValue($price, 'currency', null);
        }

        return $instance;
    }
}
