<?php

namespace Logeecom\Tests\BusinessLogic\Subscription;

use Packlink\BusinessLogic\Http\DTO\Subscription\Subscription;
use PHPUnit\Framework\TestCase;

/**
 * Class SubscriptionDtoTest.
 *
 * @package Logeecom\Tests\BusinessLogic\Subscription
 */
class SubscriptionDtoTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function loadFixture()
    {
        $contents = file_get_contents(__DIR__ . '/../Common/ApiResponses/subscription.json');

        return json_decode($contents, true);
    }

    public function testFromArray()
    {
        $raw = $this->loadFixture();

        $subscription = Subscription::fromArray($raw);

        self::assertSame('sub_abc123', $subscription->id);
        self::assertSame('client_xyz789', $subscription->clientId);
        self::assertSame('2025-01-15T10:30:00Z', $subscription->activatedAt);
        self::assertSame('EUR', $subscription->currentBillingCurrency);
        self::assertSame(29.99, $subscription->currentBillingAmount);
        self::assertInstanceOf('Packlink\BusinessLogic\Http\DTO\Subscription\SubscriptionPlan', $subscription->plan);
    }

    public function testToArray()
    {
        $raw = $this->loadFixture();

        $subscription = Subscription::fromArray($raw);

        self::assertEquals($raw, $subscription->toArray());
    }

    public function testNestedPlanFromArray()
    {
        $raw = $this->loadFixture();

        $subscription = Subscription::fromArray($raw);

        self::assertSame('plan_plus', $subscription->plan->id);
        self::assertSame('plus_legacy_es', $subscription->plan->code);
        self::assertSame('Plus', $subscription->plan->name);
    }

    public function testGetPlanTierFree()
    {
        $subscription = $this->subscriptionWithPlanName('Free');

        self::assertSame('FREE', $subscription->getPlanTier());
    }

    public function testGetPlanTierPlus()
    {
        $subscription = $this->subscriptionWithPlanName('Plus');

        self::assertSame('PLUS', $subscription->getPlanTier());
    }

    public function testGetPlanTierPremium()
    {
        $subscription = $this->subscriptionWithPlanName('Premium');

        self::assertSame('PREMIUM', $subscription->getPlanTier());
    }

    public function testGetPlanTierCaseInsensitive()
    {
        self::assertSame('FREE', $this->subscriptionWithPlanName('free')->getPlanTier());
        self::assertSame('FREE', $this->subscriptionWithPlanName('FREE')->getPlanTier());
        self::assertSame('FREE', $this->subscriptionWithPlanName('FrEe')->getPlanTier());
        self::assertSame('PLUS', $this->subscriptionWithPlanName('  plus  ')->getPlanTier());
    }

    public function testGetPlanTierUnknownDefaultsToFree()
    {
        self::assertSame('FREE', $this->subscriptionWithPlanName('Enterprise')->getPlanTier());
        self::assertSame('FREE', $this->subscriptionWithPlanName('')->getPlanTier());
    }

    public function testGetPlanTierNullPlanDefaultsToFree()
    {
        $subscription = Subscription::fromArray(array(
            'id' => 'sub_1',
            'client_id' => 'client_1',
            'current_billing_currency' => 'EUR',
            'current_billing_amount' => 0,
        ));

        self::assertNull($subscription->plan);
        self::assertSame('FREE', $subscription->getPlanTier());
    }

    public function testFromArrayWithNullActivatedAt()
    {
        $subscription = Subscription::fromArray(array(
            'id' => 'sub_1',
            'client_id' => 'client_1',
            'activated_at' => null,
            'current_billing_currency' => 'EUR',
            'current_billing_amount' => 0,
        ));

        self::assertNull($subscription->activatedAt);
    }

    /**
     * @param string $name
     *
     * @return Subscription
     */
    private function subscriptionWithPlanName($name)
    {
        return Subscription::fromArray(array(
            'id' => 'sub_1',
            'client_id' => 'client_1',
            'activated_at' => '2025-01-01T00:00:00Z',
            'current_billing_currency' => 'EUR',
            'current_billing_amount' => 0,
            'plan' => array(
                'id' => 'plan_1',
                'code' => 'code_1',
                'name' => $name,
            ),
        ));
    }
}
