<?php

namespace Logeecom\Tests\BusinessLogic\Subscription;

use Packlink\BusinessLogic\Http\DTO\Subscription\SubscriptionPlan;
use PHPUnit\Framework\TestCase;

/**
 * Class SubscriptionPlanDtoTest.
 *
 * @package Logeecom\Tests\BusinessLogic\Subscription
 */
class SubscriptionPlanDtoTest extends TestCase
{
    public function testFromArray()
    {
        $raw = array(
            'id' => 'plan_plus',
            'code' => 'plus_legacy_es',
            'name' => 'Plus',
        );

        $plan = SubscriptionPlan::fromArray($raw);

        self::assertSame('plan_plus', $plan->id);
        self::assertSame('plus_legacy_es', $plan->code);
        self::assertSame('Plus', $plan->name);
    }

    public function testToArray()
    {
        $raw = array(
            'id' => 'plan_premium',
            'code' => 'premium_2024',
            'name' => 'Premium',
        );

        $plan = SubscriptionPlan::fromArray($raw);

        self::assertSame($raw, $plan->toArray());
    }

    public function testFromArrayWithMissingFields()
    {
        $plan = SubscriptionPlan::fromArray(array());

        self::assertSame('', $plan->id);
        self::assertSame('', $plan->code);
        self::assertSame('', $plan->name);
    }
}
