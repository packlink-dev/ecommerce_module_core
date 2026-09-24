<?php

namespace Logeecom\Tests\BusinessLogic\DDP;

use PHPUnit\Framework\TestCase;
use Packlink\BusinessLogic\DDP\DdpBehavior;
use Packlink\BusinessLogic\DDP\DdpCostComposer;
use Packlink\BusinessLogic\DDP\Models\DdpCostResponse;
use Packlink\BusinessLogic\Http\DTO\DDP\DdpProductCost;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;

/**
 * Class DdpCostComposerTest. Locks down the arithmetic that turns Packlink's duty components into the
 * amount a shopper is charged.
 *
 * These rules previously lived in three integrations at once and had begun to drift, so the point of
 * the suite is less to prove the sums than to pin the edges the three copies disagreed on or got
 * wrong in production: an absent component is not a zero duty, a composed zero is real money, the
 * amount is floored before it is rounded and rounded exactly once, and a figure whose currency is
 * unknown is never charged.
 *
 * Floats are compared through assertMoney() rather than a delta parameter: PHPUnit 4.8 takes a delta
 * as assertEquals()' fourth argument, 9.6 deprecates it and 11 has removed it, and core supports all
 * three.
 *
 * @package Logeecom\Tests\BusinessLogic\DDP
 */
class DdpCostComposerTest extends TestCase
{
    /**
     * Both enabled components compose to their raw sum, unadjusted and unrounded: this is the figure
     * an integration caches for the whole cart and prices every eligible method from.
     *
     * @return void
     */
    public function testComposeBaseSumsTheEnabledComponents()
    {
        $this->assertMoney(
            24.51,
            DdpCostComposer::composeBase($this->response(5.76, 18.75)),
            'Both enabled components should compose to their sum.'
        );
    }

    /**
     * A disabled component does not apply on this route, so it contributes nothing.
     *
     * @return void
     */
    public function testDisabledComponentDoesNotContributeToTheBase()
    {
        $response = $this->response(5.76, 18.75);
        $response->customsAndDuties = $this->component(18.75, 'EUR', false);

        $this->assertMoney(
            5.76,
            DdpCostComposer::composeBase($response),
            'Only the enabled component should contribute.'
        );
    }

    /**
     * A response carrying only one of the two components still composes: the missing one is simply
     * absent from the sum rather than aborting the composition.
     *
     * @return void
     */
    public function testASingleComponentStillComposes()
    {
        $response = $this->response(5.76, 18.75);
        $response->ddpFee = null;

        $this->assertMoney(18.75, DdpCostComposer::composeBase($response), 'One component is enough.');
    }

    /**
     * No enabled component means the route carries no duty for this service, and that is NOT a zero
     * duty: 0.00 is indistinguishable from a real duty of nothing and would put a second, identically
     * priced service in front of the shopper. Callers read null as "no duties product here".
     *
     * @return void
     */
    public function testNoEnabledComponentComposesToNullRatherThanZero()
    {
        $response = $this->response(5.76, 18.75);
        $response->ddpFee = $this->component(5.76, 'EUR', false);
        $response->customsAndDuties = $this->component(18.75, 'EUR', false);

        $this->assertNull(
            DdpCostComposer::composeBase($response),
            'An absent duty must not be composed as a zero duty.'
        );
    }

    /**
     * Nothing to compose from at all is the same answer, so an integration whose lookup failed can
     * pass the null straight through.
     *
     * @return void
     */
    public function testAbsentResponseComposesToNull()
    {
        $this->assertNull(DdpCostComposer::composeBase(null));
        $this->assertNull(DdpCostComposer::ddpFee(null));
        $this->assertNull(DdpCostComposer::customsAndDuties(null));
        $this->assertNull(DdpCostComposer::quotedCurrency(null));
    }

    /**
     * A method with no adjustment charges Packlink's own figure, rounded once.
     *
     * @return void
     */
    public function testNoAdjustmentChargesPacklinksOwnFigure()
    {
        $this->assertMoney(
            24.51,
            DdpCostComposer::charged($this->response(5.76, 18.75), $this->method(null, 0.0)),
            'An unconfigured adjustment must leave the amount alone.'
        );
    }

    /**
     * A signed fixed adjustment moves the amount in both directions. Both halves are asserted
     * together because a subtraction that behaves like an addition is the exact defect reported from
     * the field: "-25 is same as 25".
     *
     * @return void
     */
    public function testFixedAdjustmentIsSigned()
    {
        $base = 24.51;

        $this->assertMoney(
            29.51,
            DdpCostComposer::applyAdjustment($base, DdpBehavior::ADJUSTMENT_FIXED, 5.00),
            'A positive fixed adjustment must add.'
        );
        $this->assertMoney(
            19.51,
            DdpCostComposer::applyAdjustment($base, DdpBehavior::ADJUSTMENT_FIXED, -5.00),
            'A negative fixed adjustment must subtract, not add.'
        );
    }

    /**
     * The same in both directions for a percentage.
     *
     * @return void
     */
    public function testPercentageAdjustmentIsSigned()
    {
        $base = 24.51;

        $this->assertMoney(
            30.64,
            DdpCostComposer::applyAdjustment($base, DdpBehavior::ADJUSTMENT_PERCENTAGE, 25.0),
            'A positive percentage must add.'
        );
        $this->assertMoney(
            18.38,
            DdpCostComposer::applyAdjustment($base, DdpBehavior::ADJUSTMENT_PERCENTAGE, -25.0),
            'A negative percentage must subtract, not add.'
        );
    }

    /**
     * An adjustment large enough to take the duty below zero is floored: a negative duty would price
     * the duties-paid rate below the plain rate for the same service.
     *
     * @return void
     */
    public function testAdjustmentIsFlooredAtZero()
    {
        $this->assertMoney(
            0.00,
            DdpCostComposer::applyAdjustment(24.51, DdpBehavior::ADJUSTMENT_FIXED, -100.00),
            'A duty must never come out negative.'
        );
        $this->assertMoney(
            0.00,
            DdpCostComposer::applyAdjustment(24.51, DdpBehavior::ADJUSTMENT_PERCENTAGE, -100.0),
            'An adjustment that cancels the duty charges 0.00.'
        );
    }

    /**
     * Flooring happens before the rounding, not after. Reversed, -0.004 rounds to -0.00 and slips
     * through the floor as a negative zero, which then reaches a payment total as "-0.00".
     *
     * @return void
     */
    public function testFlooringPrecedesRoundingSoNoNegativeZeroEscapes()
    {
        $charged = DdpCostComposer::applyAdjustment(0.001, DdpBehavior::ADJUSTMENT_FIXED, -0.005);

        $this->assertSame(
            '0.00',
            sprintf('%.2f', $charged),
            'A floored amount must be positive zero, never -0.00.'
        );
    }

    /**
     * An amount with no recognised type is a half-saved configuration, and charging an unspecified
     * adjustment is worse than charging none.
     *
     * @return void
     */
    public function testAmountWithNoTypeIsIgnored()
    {
        $this->assertMoney(24.51, DdpCostComposer::applyAdjustment(24.51, null, 5.00));
        $this->assertMoney(24.51, DdpCostComposer::applyAdjustment(24.51, '', 5.00));
        $this->assertMoney(24.51, DdpCostComposer::applyAdjustment(24.51, 'multiplier', 5.00));
    }

    /**
     * The amount is rounded here and nowhere else, so repeated reads of the same base cannot drift.
     * A base of 10.01 at +33.33% is a repeating decimal before rounding, the case where an unrounded
     * intermediate would surface as a different value on a second read.
     *
     * @return void
     */
    public function testChargedAmountIsAlreadyRoundedAndStableAcrossReads()
    {
        $method = $this->method(DdpBehavior::ADJUSTMENT_PERCENTAGE, 33.33);

        $first = DdpCostComposer::applyAdjustment(10.01, $method->getDdpAdjustmentType(), 33.33);
        $second = DdpCostComposer::applyAdjustment(10.01, $method->getDdpAdjustmentType(), 33.33);

        $this->assertSame($first, $second, 'The composed amount must not change between reads.');
        $this->assertSame(
            round($first, 2),
            $first,
            'The amount must arrive already rounded, never rounded again downstream.'
        );
    }

    /**
     * The adjustment is read off the method when the amount is wanted, not when the base was quoted,
     * so the same cached base reprices the instant a merchant edits it. This is why a cache holds the
     * base and never the composed amount.
     *
     * @return void
     */
    public function testTheSameBaseRepricesWhenTheAdjustmentChanges()
    {
        $method = $this->method(DdpBehavior::ADJUSTMENT_FIXED, 5.00);

        $this->assertMoney(29.51, DdpCostComposer::chargedFromBase(24.51, $method));

        $method->setDdpAdjustmentAmount(-1.00);

        $this->assertMoney(
            23.51,
            DdpCostComposer::chargedFromBase(24.51, $method),
            'An edited adjustment must take effect without the base being quoted again.'
        );
    }

    /**
     * The property that keeps a cache honest: re-composing from persisted components must land on the
     * same amount as composing from the live response. Serving a stored, already-adjusted amount
     * instead is a bug that shipped once -- an adjustment edited mid-cart did not reach the checkout
     * until the quote expired -- and this is the assertion that would have caught it.
     *
     * @return void
     */
    public function testRecomposingFromPersistedComponentsMatchesTheLiveComposition()
    {
        $response = $this->response(5.76, 18.75);
        $method = $this->method(DdpBehavior::ADJUSTMENT_PERCENTAGE, 12.5);

        $this->assertSame(
            DdpCostComposer::charged($response, $method),
            DdpCostComposer::chargedFromComponents(
                DdpCostComposer::ddpFee($response),
                DdpCostComposer::customsAndDuties($response),
                $method
            ),
            'A quote re-composed from its stored components must not drift from the live path.'
        );
    }

    /**
     * Persisted components are stored before any adjustment (AC-8.1.1), which is what makes the
     * re-composition above possible, and a disabled component is stored as null rather than 0.00.
     *
     * @return void
     */
    public function testComponentsArePersistedUnadjustedAndAbsentWhenDisabled()
    {
        $response = $this->response(5.76, 18.75);
        $response->ddpAdjustmentType = DdpBehavior::ADJUSTMENT_FIXED;
        $response->ddpAdjustmentAmount = 99.0;

        $this->assertMoney(5.76, DdpCostComposer::ddpFee($response), 'Stored raw, without the adjustment.');
        $this->assertMoney(18.75, DdpCostComposer::customsAndDuties($response));

        $response->customsAndDuties = $this->component(18.75, 'EUR', false);

        $this->assertNull(
            DdpCostComposer::customsAndDuties($response),
            'A disabled component is absent, not zero.'
        );
    }

    /**
     * Neither component stored means there was no duties product, so there is nothing to re-compose.
     *
     * @return void
     */
    public function testRecomposingWithNoStoredComponentsIsNull()
    {
        $method = $this->method(DdpBehavior::ADJUSTMENT_FIXED, 5.00);

        $this->assertNull(DdpCostComposer::chargedFromComponents(null, null, $method));
        $this->assertMoney(
            10.76,
            DdpCostComposer::chargedFromComponents(5.76, null, $method),
            'One stored component is enough to re-compose from.'
        );
    }

    /**
     * A duty priced in the currency the cart charges in is usable, and the comparison ignores case
     * and surrounding space rather than refusing over presentation.
     *
     * @return void
     */
    public function testMatchingCurrencyIsUsable()
    {
        $response = $this->response(5.76, 18.75);

        $this->assertSame(DdpCostComposer::CURRENCY_USABLE, DdpCostComposer::checkCurrency($response, 'EUR'));
        $this->assertSame(DdpCostComposer::CURRENCY_USABLE, DdpCostComposer::checkCurrency($response, 'eur'));
        $this->assertSame(DdpCostComposer::CURRENCY_USABLE, DdpCostComposer::checkCurrency($response, ' EUR '));
        $this->assertSame('EUR', DdpCostComposer::quotedCurrency($response));
    }

    /**
     * Core hands the amounts over unconverted, so a duty quoted in another currency is numerically
     * wrong money the moment it is added to a total. Refused, never converted.
     *
     * @return void
     */
    public function testForeignCurrencyIsRefusedAndReportable()
    {
        $response = $this->response(5.76, 18.75);
        $response->ddpFee = $this->component(5.76, 'USD');
        $response->customsAndDuties = $this->component(18.75, 'USD');

        $this->assertSame(DdpCostComposer::CURRENCY_FOREIGN, DdpCostComposer::checkCurrency($response, 'EUR'));
        $this->assertSame(
            'USD',
            DdpCostComposer::quotedCurrency($response),
            'The offending currency must be reportable, so the log names what was actually quoted.'
        );
    }

    /**
     * An enabled component carrying an amount but no currency is a number with no unit. Packlink's
     * DTO defaults a missing `currency` to an empty string, so this is a real response shape.
     *
     * It gets its own code rather than being folded into either neighbour: reported as a mismatch it
     * sends the merchant looking for an FX problem that does not exist, and reported as "no duty on
     * this route" it hides a quote that was actually refused.
     *
     * @return void
     */
    public function testComponentWithNoCurrencyIsRefusedDistinctly()
    {
        $response = $this->response(5.76, 18.75);
        $response->ddpFee = $this->component(5.76, '');
        $response->customsAndDuties = $this->component(18.75, '');

        $this->assertSame(DdpCostComposer::CURRENCY_UNQUOTED, DdpCostComposer::checkCurrency($response, 'EUR'));
        $this->assertNull(
            DdpCostComposer::quotedCurrency($response),
            'A currency nobody quoted must never be reported as if it were.'
        );
        $this->assertMoney(
            24.51,
            DdpCostComposer::composeBase($response),
            'The arithmetic still composes; it is the caller that refuses on the currency.'
        );
    }

    /**
     * A currency named on either component answers for the quote, so an omission on the first one
     * does not lose a unit the second one did state.
     *
     * @return void
     */
    public function testCurrencyIsReadFromWhicheverComponentNamesOne()
    {
        $response = $this->response(5.76, 18.75);
        $response->ddpFee = $this->component(5.76, '');

        $this->assertSame('EUR', DdpCostComposer::quotedCurrency($response));
        $this->assertSame(DdpCostComposer::CURRENCY_USABLE, DdpCostComposer::checkCurrency($response, 'EUR'));
    }

    /**
     * A cart whose own currency will not resolve leaves nothing to verify the quote against, and an
     * unverifiable figure is refused on the same principle as an unquoted one.
     *
     * @return void
     */
    public function testUnresolvableCartCurrencyIsRefused()
    {
        $response = $this->response(5.76, 18.75);

        $this->assertSame(
            DdpCostComposer::CURRENCY_UNVERIFIABLE,
            DdpCostComposer::checkCurrency($response, ''),
            'Nothing to compare against is not the same as a match.'
        );
    }

    /**
     * A disabled component's currency is not the quote's currency, so it can neither supply the unit
     * nor cause a mismatch.
     *
     * @return void
     */
    public function testDisabledComponentsCurrencyIsIgnored()
    {
        $response = $this->response(5.76, 18.75);
        $response->ddpFee = $this->component(5.76, 'USD', false);

        $this->assertSame('EUR', DdpCostComposer::quotedCurrency($response));
        $this->assertSame(DdpCostComposer::CURRENCY_USABLE, DdpCostComposer::checkCurrency($response, 'EUR'));
    }

    /**
     * Asserts two money amounts are equal at cent precision.
     *
     * Rounds before comparing rather than passing a delta, which is spelled differently in each of
     * the three PHPUnit majors core supports.
     *
     * @param float $expected Expected amount.
     * @param float|null $actual Actual amount.
     * @param string $message Failure message.
     *
     * @return void
     */
    private function assertMoney($expected, $actual, $message = '')
    {
        $this->assertNotNull($actual, $message !== '' ? $message : 'Expected an amount, got null.');
        $this->assertSame(round((float)$expected, 2), round((float)$actual, 2), $message);
    }

    /**
     * A response with both components enabled and priced in EUR.
     *
     * @param float $ddpFee ddp_fee total.
     * @param float $customsAndDuties customs_and_duties total.
     *
     * @return DdpCostResponse
     */
    private function response($ddpFee, $customsAndDuties)
    {
        $response = new DdpCostResponse();
        $response->serviceId = '2001';
        $response->ddpFee = $this->component($ddpFee, 'EUR');
        $response->customsAndDuties = $this->component($customsAndDuties, 'EUR');
        $response->effectiveBehavior = DdpBehavior::OPTIONAL;

        return $response;
    }

    /**
     * One duty component.
     *
     * @param float $totalPrice Component total.
     * @param string $currency ISO code, empty to omit it as Packlink's DTO does.
     * @param bool $isEnabled Whether the component applies on this route.
     *
     * @return DdpProductCost
     */
    private function component($totalPrice, $currency, $isEnabled = true)
    {
        $component = new DdpProductCost();
        $component->basePrice = $totalPrice;
        $component->taxPrice = 0.0;
        $component->totalPrice = $totalPrice;
        $component->currency = $currency;
        $component->isEnabled = $isEnabled;
        $component->isSelected = $isEnabled;

        return $component;
    }

    /**
     * A shipping method carrying one merchant adjustment.
     *
     * @param string|null $type Adjustment type.
     * @param float $amount Signed adjustment value.
     *
     * @return ShippingMethod
     */
    private function method($type, $amount)
    {
        $method = new ShippingMethod();
        $method->setDdpBehavior(DdpBehavior::OPTIONAL);
        $method->setDdpAdjustmentType($type);
        $method->setDdpAdjustmentAmount($amount);

        return $method;
    }
}
