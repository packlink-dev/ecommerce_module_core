<?php

namespace Logeecom\Tests\BusinessLogic\ShippingMethod;

use Packlink\BusinessLogic\ShippingMethod\Models\FixedPricePolicy;
use Packlink\BusinessLogic\ShippingMethod\Models\PercentPricePolicy;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use PHPUnit\Framework\TestCase;

/**
 * Class ShippingMethodEntityTest.
 *
 * @package Logeecom\Tests\BusinessLogic\ShippingMethod
 */
class ShippingMethodEntityTest extends TestCase
{
    public function testProperties()
    {
        $method = new ShippingMethod();
        $method->setServiceId(1234);
        self::assertEquals(1234, $method->getServiceId());
        $method->setServiceName('service name');
        self::assertEquals('service name', $method->getServiceName());
        $method->setCarrierName('carrier name');
        self::assertEquals('carrier name', $method->getCarrierName());
        $method->setTitle('title');
        self::assertEquals('title', $method->getTitle());
        $method->setEnabled(false);
        self::assertFalse($method->isEnabled());
        $method->setActivated(true);
        self::assertTrue($method->isActivated());
        $method->setLogoUrl('https://packlink.com');
        self::assertEquals('https://packlink.com', $method->getLogoUrl());
        $method->setDisplayLogo(false);
        self::assertFalse($method->isDisplayLogo());
        $method->setDepartureDropOff(true);
        self::assertTrue($method->isDepartureDropOff());
        $method->setDestinationDropOff(true);
        self::assertTrue($method->isDestinationDropOff());
        $method->setExpressDelivery(true);
        self::assertTrue($method->isExpressDelivery());
        $method->setDeliveryTime('2 DAYS');
        self::assertEquals('2 DAYS', $method->getDeliveryTime());
        $method->setNational(true);
        self::assertTrue($method->isNational());
    }

    public function testDefaultPricingPolicy()
    {
        $method = new ShippingMethod();

        self::assertEmpty($method->getFixedPricePolicy());
        self::assertEmpty($method->getPercentPricePolicy());
        self::assertEquals(ShippingMethod::PRICING_POLICY_PACKLINK, $method->getPricingPolicy());
    }

    public function testFixedPricingPolicyOneValid()
    {
        $method = new ShippingMethod();

        /** @var FixedPricePolicy[] $fixedPricePolicies */
        $fixedPricePolicies[] = new FixedPricePolicy(0, 10, 12);

        $method->setFixedPricePolicy($fixedPricePolicies);

        self::assertNotEmpty($method->getFixedPricePolicy());
        self::assertEmpty($method->getPercentPricePolicy());
        self::assertEquals(ShippingMethod::PRICING_POLICY_FIXED, $method->getPricingPolicy());
    }

    public function testFixedPricingPolicyValidationAmountNotSetOnFirst()
    {
        $method = new ShippingMethod();

        /** @var FixedPricePolicy[] $fixedPricePolicies */
        $fixedPricePolicies[] = new FixedPricePolicy(0, 10, 0);

        // amount must be set
        $this->expectException('\InvalidArgumentException');
        $method->setFixedPricePolicy($fixedPricePolicies);
    }

    public function testFixedPricingPolicyValidationNegativeAmountOnFirst()
    {
        $method = new ShippingMethod();

        /** @var FixedPricePolicy[] $fixedPricePolicies */
        $fixedPricePolicies[] = new FixedPricePolicy(0, 10, -10);

        // amount must be positive
        $this->expectException('\InvalidArgumentException');
        $method->setFixedPricePolicy($fixedPricePolicies);
    }

    public function testFixedPricingPolicyValidationZeroAmountOnFirst()
    {
        $method = new ShippingMethod();

        /** @var FixedPricePolicy[] $fixedPricePolicies */
        $fixedPricePolicies[] = new FixedPricePolicy(0, 10, 0);

        // amount must be positive
        $this->expectException('\InvalidArgumentException');
        $method->setFixedPricePolicy($fixedPricePolicies);
    }

    public function testFixedPricingPolicyValidationFromNotZeroOnFirst()
    {
        $method = new ShippingMethod();

        /** @var FixedPricePolicy[] $fixedPricePolicies */
        $fixedPricePolicies[] = new FixedPricePolicy(3, 10, 10);

        // from for first policy must be 0
        $this->expectException('\InvalidArgumentException');
        $method->setFixedPricePolicy($fixedPricePolicies);
    }

    public function testFixedPricingPolicyValidationValidTwoPolicies()
    {
        $method = new ShippingMethod();

        /** @var FixedPricePolicy[] $fixedPricePolicies */
        $fixedPricePolicies[] = new FixedPricePolicy(0, 10, 12);
        $fixedPricePolicies[] = new FixedPricePolicy(10, 13, 10);

        $method->setFixedPricePolicy($fixedPricePolicies);
        self::assertNotEmpty($method->getFixedPricePolicy());
        self::assertCount(2, $method->getFixedPricePolicy());
    }

    public function testFixedPricingPolicyValidationValidTwoPoliciesSecondWithoutUpperBound()
    {
        $method = new ShippingMethod();

        /** @var FixedPricePolicy[] $fixedPricePolicies */
        $fixedPricePolicies[] = new FixedPricePolicy(0, 10, 12);
        $fixedPricePolicies[] = new FixedPricePolicy(10, 20, 10);

        $method->setFixedPricePolicy($fixedPricePolicies);
        self::assertNotEmpty($method->getFixedPricePolicy());
        self::assertCount(2, $method->getFixedPricePolicy());
    }

    public function testFixedPricingPolicyValidationInvalidFromBetweenPolicies()
    {
        $method = new ShippingMethod();

        /** @var FixedPricePolicy[] $fixedPricePolicies */
        $fixedPricePolicies[] = new FixedPricePolicy(0, 10, 12);
        $fixedPricePolicies[] = new FixedPricePolicy(11, 13, 10);

        // second policy must have "from" bigger from previous for exactly 0.001
        $this->expectException('\InvalidArgumentException');
        $method->setFixedPricePolicy($fixedPricePolicies);
    }

    public function testFixedPricingPolicyValidationNegativeAmountOnSecondPolicy()
    {
        $method = new ShippingMethod();

        /** @var FixedPricePolicy[] $fixedPricePolicies */
        $fixedPricePolicies[] = new FixedPricePolicy(0, 10, 12);
        $fixedPricePolicies[] = new FixedPricePolicy(10, 13, -10);

        // second policy must have amount bigger than 0
        $this->expectException('\InvalidArgumentException');
        $method->setFixedPricePolicy($fixedPricePolicies);
    }

    public function testFixedPricingPolicyValidationValidBulk()
    {
        $method = new ShippingMethod();

        /** @var FixedPricePolicy[] $fixedPricePolicies */
        $fixedPricePolicies[] = new FixedPricePolicy(0, 13, 1000);

        for ($i = 1; $i < 100; $i++) {
            $fixedPricePolicies[] = new FixedPricePolicy(
                $fixedPricePolicies[$i - 1]->to,
                $fixedPricePolicies[$i - 1]->to + 10,
                $fixedPricePolicies[$i - 1]->amount - 10
            );
        }

        $method->setFixedPricePolicy($fixedPricePolicies);
        self::assertCount(100, $method->getFixedPricePolicy());
    }

    public function testPercentPricingPolicy()
    {
        $method = new ShippingMethod();

        $policy = new PercentPricePolicy(true, 10);
        $method->setPercentPricePolicy($policy);

        self::assertNotEmpty($method->getPercentPricePolicy());
        self::assertEquals(ShippingMethod::PRICING_POLICY_PERCENT, $method->getPricingPolicy());
        self::assertEmpty($method->getFixedPricePolicy());

        $policy = new PercentPricePolicy(false, 10);
        $method->setPercentPricePolicy($policy);

        self::assertNotEmpty($method->getPercentPricePolicy());
        self::assertEquals(ShippingMethod::PRICING_POLICY_PERCENT, $method->getPricingPolicy());
    }

    public function testPercentPricingPolicyZeroAmount()
    {
        $method = new ShippingMethod();

        $policy = new PercentPricePolicy();

        $this->expectException('\InvalidArgumentException');
        $method->setPercentPricePolicy($policy);
    }

    public function testPercentPricingPolicyNegativeAmount()
    {
        $method = new ShippingMethod();

        $policy = new PercentPricePolicy(true, -10);

        $this->expectException('\InvalidArgumentException');
        $method->setPercentPricePolicy($policy);
    }

    public function testPercentPricingPolicyDecreaseFor100()
    {
        $method = new ShippingMethod();

        $policy = new PercentPricePolicy(false, 100);

        $this->expectException('\InvalidArgumentException');
        $method->setPercentPricePolicy($policy);
    }

    public function testPercentPricingPolicyDecreaseForMoreThan100()
    {
        $method = new ShippingMethod();

        $policy = new PercentPricePolicy(false, 120);

        $this->expectException('\InvalidArgumentException');
        $method->setPercentPricePolicy($policy);
    }

    public function testPercentPricingPolicyAfterFixedPricePolicy()
    {
        $method = new ShippingMethod();

        $fixedPricePolicies[] = new FixedPricePolicy(0, 10, 12);

        $method->setFixedPricePolicy($fixedPricePolicies);

        self::assertNotEmpty($method->getFixedPricePolicy());
        self::assertEmpty($method->getPercentPricePolicy());
        self::assertEquals(ShippingMethod::PRICING_POLICY_FIXED, $method->getPricingPolicy());

        $method->setPercentPricePolicy(new PercentPricePolicy(true, 10));

        self::assertNotEmpty($method->getPercentPricePolicy());
        self::assertEquals(ShippingMethod::PRICING_POLICY_PERCENT, $method->getPricingPolicy());
        self::assertEmpty($method->getFixedPricePolicy());
    }

    public function testFixedPricingPolicyAfterPercentPricePolicy()
    {
        $method = new ShippingMethod();

        $fixedPricePolicies[] = new FixedPricePolicy(0, 10, 12);

        $method->setPercentPricePolicy(new PercentPricePolicy(true, 10));

        self::assertNotEmpty($method->getPercentPricePolicy());
        self::assertEquals(ShippingMethod::PRICING_POLICY_PERCENT, $method->getPricingPolicy());
        self::assertEmpty($method->getFixedPricePolicy());

        $method->setFixedPricePolicy($fixedPricePolicies);

        self::assertNotEmpty($method->getFixedPricePolicy());
        self::assertEmpty($method->getPercentPricePolicy());
        self::assertEquals(ShippingMethod::PRICING_POLICY_FIXED, $method->getPricingPolicy());
    }

    public function testResetAfterFixedPricingPolicy()
    {
        $method = new ShippingMethod();

        $fixedPricePolicies[] = new FixedPricePolicy(0, 10, 12);

        $method->setFixedPricePolicy($fixedPricePolicies);

        self::assertNotEmpty($method->getFixedPricePolicy());
        self::assertEmpty($method->getPercentPricePolicy());
        self::assertEquals(ShippingMethod::PRICING_POLICY_FIXED, $method->getPricingPolicy());

        $method->setPacklinkPricePolicy();
        self::assertEmpty($method->getFixedPricePolicy());
        self::assertEmpty($method->getPercentPricePolicy());
        self::assertEquals(ShippingMethod::PRICING_POLICY_PACKLINK, $method->getPricingPolicy());
    }

    public function testResetAfterPercentPricingPolicy()
    {
        $method = new ShippingMethod();

        $fixedPricePolicies[] = new FixedPricePolicy(0, 10, 12);

        $method->setPercentPricePolicy(new PercentPricePolicy(true, 10));

        self::assertNotEmpty($method->getPercentPricePolicy());
        self::assertEquals(ShippingMethod::PRICING_POLICY_PERCENT, $method->getPricingPolicy());
        self::assertEmpty($method->getFixedPricePolicy());

        $method->setPacklinkPricePolicy();
        self::assertEmpty($method->getFixedPricePolicy());
        self::assertEmpty($method->getPercentPricePolicy());
        self::assertEquals(ShippingMethod::PRICING_POLICY_PACKLINK, $method->getPricingPolicy());
    }
}
