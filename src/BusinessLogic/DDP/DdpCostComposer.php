<?php

namespace Packlink\BusinessLogic\DDP;

use Packlink\BusinessLogic\DDP\Models\DdpCostResponse;
use Packlink\BusinessLogic\Http\DTO\DDP\DdpProductCost;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;

/**
 * Class DdpCostComposer. Turns Packlink's duty components into the single amount a shopper is
 * charged (CR-SET-68 AC-7.3.1 / AC-7.3.7 / AC-7.3.8).
 *
 * This used to be platform scope, and DdpCostResponse carried a docblock saying so. Three
 * integrations then implemented the identical arithmetic independently -- Shopify's DdpCostComposer,
 * PrestaShop's DdpCostComposer, WooCommerce's Ddp_Cost_Calculator -- and had already begun to drift
 * on the currency rule while still agreeing on the sums. The composition depends on nothing but a
 * core DTO and a core ShippingMethod, so there was never a reason for a platform to own a copy: it
 * lives here now, and a wrapper is free to re-expose it in the platform's own naming and typing.
 *
 * Every method is static and side-effect free. Nothing is logged from here: a refusal is returned as
 * a code so each integration can word it in its own voice, which is also why the currency questions
 * are kept apart from the arithmetic.
 *
 * Four rules, each present because of a specific way the amount goes wrong without it:
 *
 *  - **Only enabled components count, and an absent component is not zero.** A route with no duty
 *    omits ddp_fee entirely, which is a normal answer (AC-7.3.4). Reading that as 0.00 turns "no duty
 *    applies" into "a duty of nothing" and offers the shopper a second, identically priced service.
 *    composeBase() therefore answers null for it, and callers must never substitute 0.0.
 *  - **A composed zero is real money and is charged as 0.00.** Packlink legitimately quotes no duty
 *    on some routes -- a consignment under the destination's de-minimis threshold -- and the order is
 *    still a duties-paid order: the draft has to carry the DDP flag, a MANDATORY service would
 *    otherwise lose its only rate, and the shopper is entitled to see that duties are covered. Only
 *    an absent component means "no duties product here".
 *  - **Floor at zero, then round exactly once.** The merchant's adjustment is signed, so a large
 *    negative one would otherwise price the duties-paid rate below the plain rate for the same
 *    service. Flooring after rounding lets -0.004 arrive as -0.00, and rounding more than once lets
 *    the checkout price, the description text and the stored quote disagree by a cent, which is
 *    unexplainable to a merchant reading their own orders page.
 *  - **The adjustment belongs to the method being priced.** Duty is a function of the goods and the
 *    route, not the carrier service, so one Packlink call answers for every eligible service on the
 *    route -- but each service's owning method carries its own adjustment. DdpCostResponse's own
 *    adjustment fields describe only the service that was queried, so the adjustment is read from the
 *    ShippingMethod and never from the response.
 *
 * @package Packlink\BusinessLogic\DDP
 */
class DdpCostComposer
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * The quote is priced in the currency the cart charges in.
     */
    const CURRENCY_USABLE = 'usable';

    /**
     * An enabled component carries an amount but no currency, so its unit is unknown. Packlink's DTO
     * defaults a missing `currency` to an empty string rather than null, so this is a real response
     * shape and not a defensive branch.
     */
    const CURRENCY_UNQUOTED = 'unquoted';

    /**
     * The integration could not resolve the currency the cart charges in, so the quote cannot be
     * verified against anything.
     */
    const CURRENCY_UNVERIFIABLE = 'unverifiable';

    /**
     * The quote is priced in a different currency than the cart charges in.
     */
    const CURRENCY_FOREIGN = 'foreign';

    /**
     * Sums the duty components that apply on this route into the raw base every method is priced from.
     *
     * Deliberately unadjusted and unrounded: duty does not vary by carrier service, so this one base
     * answers for every DDP-capable method of the cart and is the figure worth caching, while each
     * method's own adjustment is applied afterwards by applyAdjustment(). Keeping the adjustment out
     * of the cached figure is what lets a merchant's edit take effect on the next render instead of
     * when the quote expires -- caching an already-adjusted amount is a bug this shape prevents, and
     * one that has been shipped and fixed once already.
     *
     * @param DdpCostResponse|null $response Core duty cost response.
     *
     * @return float|null Raw base, or null when no component is enabled, i.e. there is no duties
     *                    product to sell on this route.
     */
    public static function composeBase($response)
    {
        $base = 0.0;
        $hasComponent = false;

        foreach (self::components($response) as $component) {
            $base += (float)$component->totalPrice;
            $hasComponent = true;
        }

        return $hasComponent ? $base : null;
    }

    /**
     * Packlink's own ddp_fee total, before any adjustment, for persistence (AC-8.1.1).
     *
     * Stored apart from the composed amount so a cached quote can be re-composed against the
     * merchant's current adjustment rather than replaying the amount composed when it was written.
     *
     * @param DdpCostResponse|null $response Core duty cost response.
     *
     * @return float|null Null when the component is absent or disabled.
     */
    public static function ddpFee($response)
    {
        return self::componentTotal($response, 'ddpFee');
    }

    /**
     * Packlink's own customs_and_duties total, before any adjustment, for persistence (AC-8.1.1).
     *
     * @param DdpCostResponse|null $response Core duty cost response.
     *
     * @return float|null Null when the component is absent or disabled.
     */
    public static function customsAndDuties($response)
    {
        return self::componentTotal($response, 'customsAndDuties');
    }

    /**
     * Applies one method's signed merchant adjustment to a raw base and finalizes the amount.
     *
     * This is the only place the amount is floored and rounded. Downstream surfaces -- the rate, the
     * option row, the cart fee, the order meta -- reuse the result and must never recompute or
     * re-round it.
     *
     * @param float $base Raw base from composeBase().
     * @param string|null $type Adjustment type, one of DdpBehavior::ADJUSTMENT_*.
     * @param float $amount Signed adjustment value of the method being priced.
     *
     * @return float Adjusted amount, floored at zero and rounded to two decimals.
     */
    public static function applyAdjustment($base, $type, $amount)
    {
        $base = (float)$base;
        $amount = (float)$amount;

        if ($amount !== 0.0) {
            if ($type === DdpBehavior::ADJUSTMENT_PERCENTAGE) {
                $base += $base * $amount / 100;
            } elseif ($type === DdpBehavior::ADJUSTMENT_FIXED) {
                $base += $amount;
            }

            // An amount with no recognised type is a half-saved configuration. Ignoring it is the safe
            // reading: charging an unspecified adjustment is worse than charging none.
        }

        // Floor first, then the single rounding. Reversing these lets -0.004 round to -0.00 and slip
        // through as a negative zero.
        return round(max(0.0, $base), 2);
    }

    /**
     * The amount charged for one method, composed from a live response.
     *
     * @param DdpCostResponse|null $response Core duty cost response.
     * @param ShippingMethod $method The method being priced, which owns the adjustment.
     *
     * @return float|null Composed amount, 0.00 when the duty is genuinely nil; null only when no
     *                    component is enabled at all.
     */
    public static function charged($response, ShippingMethod $method)
    {
        return self::chargedFromBase(self::composeBase($response), $method);
    }

    /**
     * The amount charged for one method, composed from Packlink's persisted components rather than
     * from a live response.
     *
     * The path a cached quote takes: the stored ddp_fee and customs_and_duties are re-composed
     * against the merchant's *current* adjustment. Duplicating the arithmetic at the cache site
     * instead is what lets the cached and freshly quoted paths drift apart.
     *
     * @param float|null $ddpFee Packlink's ddp_fee total, before adjustment.
     * @param float|null $customsAndDuties Packlink's customs_and_duties total, before adjustment.
     * @param ShippingMethod $method The method being priced, which owns the adjustment.
     *
     * @return float|null Null only when neither component was stored, i.e. there is no duties product.
     */
    public static function chargedFromComponents($ddpFee, $customsAndDuties, ShippingMethod $method)
    {
        if ($ddpFee === null && $customsAndDuties === null) {
            return null;
        }

        return self::chargedFromBase((float)$ddpFee + (float)$customsAndDuties, $method);
    }

    /**
     * The amount charged for one method, composed from an already-summed base.
     *
     * @param float|null $base Raw base from composeBase(), or null when there is no duties product.
     * @param ShippingMethod $method The method being priced, which owns the adjustment.
     *
     * @return float|null Null when $base is null.
     */
    public static function chargedFromBase($base, ShippingMethod $method)
    {
        if ($base === null) {
            return null;
        }

        return self::applyAdjustment(
            $base,
            $method->getDdpAdjustmentType(),
            $method->getDdpAdjustmentAmount()
        );
    }

    /**
     * Whether the quote may be charged in the currency the cart charges in (AC-7.3.5).
     *
     * Core hands the component amounts over unconverted, so a duty quoted in another currency is
     * numerically wrong money the moment it is added to a total, and the order would then record the
     * shop's currency against a figure that was never in it. There is no FX conversion here by
     * design: better no duties option than a silently mispriced one.
     *
     * A number whose unit cannot be established is refused on the same principle, and that is the one
     * place this tightens what two of the three integrations used to do -- an enabled component
     * naming no currency, and a cart whose currency will not resolve, were both previously charged on
     * the assumption that the shop currency was meant. Nothing is assumed here.
     *
     * The refusals are returned as distinct codes rather than one, because a caller that collapses
     * them logs "quoted in the wrong currency" for a response that named no currency at all, and the
     * merchant then goes looking for an FX problem that does not exist.
     *
     * Call this only when composeBase() returned non-null. "No enabled component" is a different
     * answer -- no duty on this route -- and is not a currency problem.
     *
     * @param DdpCostResponse|null $response Core duty cost response.
     * @param string $expectedCurrency ISO code the cart charges in; empty when unresolvable.
     *
     * @return string One of the self::CURRENCY_* codes.
     */
    public static function checkCurrency($response, $expectedCurrency)
    {
        $expected = strtoupper(trim((string)$expectedCurrency));
        $quoted = self::quotedCurrency($response);

        if ($quoted === null) {
            return self::CURRENCY_UNQUOTED;
        }

        if ($expected === '') {
            return self::CURRENCY_UNVERIFIABLE;
        }

        return $quoted === $expected ? self::CURRENCY_USABLE : self::CURRENCY_FOREIGN;
    }

    /**
     * The currency Packlink priced this route in, for the caller's log line.
     *
     * Read from an enabled component rather than assumed, and null when no enabled component names
     * one, so a caller never reports a currency nobody quoted.
     *
     * @param DdpCostResponse|null $response Core duty cost response.
     *
     * @return string|null Upper-cased ISO code, or null when none was quoted.
     */
    public static function quotedCurrency($response)
    {
        foreach (self::components($response) as $component) {
            $currency = strtoupper(trim((string)$component->currency));

            if ($currency !== '') {
                return $currency;
            }
        }

        return null;
    }

    /**
     * The response's enabled components, in the order they contribute to the base.
     *
     * @param DdpCostResponse|null $response Core duty cost response.
     *
     * @return DdpProductCost[]
     */
    private static function components($response)
    {
        if (!$response instanceof DdpCostResponse) {
            return array();
        }

        $enabled = array();

        foreach (array($response->ddpFee, $response->customsAndDuties) as $component) {
            if ($component instanceof DdpProductCost && $component->isEnabled) {
                $enabled[] = $component;
            }
        }

        return $enabled;
    }

    /**
     * One named component's total, when it is present and enabled.
     *
     * @param DdpCostResponse|null $response Core duty cost response.
     * @param string $property Property name on the response.
     *
     * @return float|null
     */
    private static function componentTotal($response, $property)
    {
        if (!$response instanceof DdpCostResponse) {
            return null;
        }

        $component = $response->{$property};

        return $component instanceof DdpProductCost && $component->isEnabled
            ? (float)$component->totalPrice
            : null;
    }
}
