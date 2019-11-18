<?php

namespace Packlink\BusinessLogic\ShippingMethod\Validation;

use Packlink\BusinessLogic\ShippingMethod\Models\FixedPricePolicy;
use Packlink\BusinessLogic\ShippingMethod\Models\PercentPricePolicy;

/**
 * Class PricingPolicyValidator.
 *
 * @package Packlink\BusinessLogic\ShippingMethod\Validation
 */
class PricingPolicyValidator
{
    /**
     * Validates whether fixed price policies are correct.
     * Rules for each policy:
     *   1. 'from' must be equal to 'to' of a previous policy, for first it must be 0
     *   2. 'to' must be greater than 'from'
     *   3. 'amount' must be a positive number or zero if parameter $allowZeroPrice is set to TRUE
     *
     * @param FixedPricePolicy[] $fixedPricePolicies Policies array to validate.
     * @param bool $allowZeroPrice Indicates whether amount can be equal to zero.
     *
     * @throws \InvalidArgumentException When range and/or amount are not valid.
     */
    public static function validateFixedPricePolicy($fixedPricePolicies, $allowZeroPrice = false)
    {
        if (count($fixedPricePolicies) > 0) {
            $count = count($fixedPricePolicies);
            $previous = $fixedPricePolicies[0];
            self::validateSingleFixedPricePolicy($previous, $previous->from, $allowZeroPrice);

            for ($i = 1; $i < $count; $i++) {
                self::validateSingleFixedPricePolicy($fixedPricePolicies[$i], $previous->to, $allowZeroPrice);
                $previous = $fixedPricePolicies[$i];
            }
        }
    }

    /**
     * Validates percent price policy.
     * Rules for policy:
     *   1. 'amount' must be a positive number
     *   2. 'amount' must be less than 100 if increase is FALSE
     *
     * @param PercentPricePolicy $policy Policy to validate.
     *
     * @throws \InvalidArgumentException When direction and/or amount are not valid.
     */
    public static function validatePercentPricePolicy(PercentPricePolicy $policy)
    {
        if ($policy->amount <= 0 || (!$policy->increase && $policy->amount >= 100)) {
            throw new \InvalidArgumentException('Percent price policy is not valid. Check direction and amounts.');
        }
    }

    /**
     * Validates single fixed price policy.
     *
     * @param FixedPricePolicy $policy Policy to validate.
     * @param float $lowerBoundary Value of 'from' field.
     * @param bool $allowZeroPrice Indicates whether amount can be equal to zero.
     *
     * @throws \InvalidArgumentException When range and/or amount are not valid.
     */
    protected static function validateSingleFixedPricePolicy($policy, $lowerBoundary, $allowZeroPrice = false)
    {
        if ((float) $lowerBoundary < 0
            || (float)$policy->from !== (float)$lowerBoundary
            || $policy->from >= $policy->to
            || ($allowZeroPrice && $policy->amount < 0)
            || (!$allowZeroPrice && $policy->amount <= 0)
        ) {
            throw new \InvalidArgumentException('Fixed price policies are not valid. Check range and amounts.');
        }
    }
}
