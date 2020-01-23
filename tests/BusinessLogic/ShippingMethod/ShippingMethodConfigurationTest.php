<?php

namespace Logeecom\Tests\BusinessLogic\ShippingMethod;

use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Packlink\BusinessLogic\Controllers\DTO\ShippingMethodConfiguration;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;

/**
 * Class ShippingMethodConfigurationTest.
 *
 * @package Logeecom\Tests\BusinessLogic\ShippingMethod
 */
class ShippingMethodConfigurationTest extends BaseTestWithServices
{
    public function testShippingMethodFromArray()
    {
        $config1 = ShippingMethodConfiguration::fromArray($this->getPacklinkPriceRawData());

        $this->assertInstanceOf('Packlink\BusinessLogic\Controllers\DTO\ShippingMethodConfiguration', $config1);
        $this->assertEquals(1, $config1->id);
        $this->assertEquals('Test', $config1->name);
        $this->assertEquals(true, $config1->showLogo);
        $this->assertEquals(ShippingMethod::PRICING_POLICY_PACKLINK, $config1->pricePolicy);

        $config2 = ShippingMethodConfiguration::fromArray($this->getPacklinkPercentPriceRawData());
        $this->assertInstanceOf(
            'Packlink\BusinessLogic\ShippingMethod\Models\PercentPricePolicy',
            $config2->percentPricePolicy
        );
        $this->assertEquals(50, $config2->percentPricePolicy->amount);
        $this->assertEquals(true, $config2->percentPricePolicy->increase);

        $config3 = ShippingMethodConfiguration::fromArray($this->getFixedPriceRawData(true));
        $this->assertCount(2, $config3->fixedPriceByWeightPolicy);
        $policy = $config3->fixedPriceByWeightPolicy[0];
        $this->assertInstanceOf('Packlink\BusinessLogic\ShippingMethod\Models\FixedPricePolicy', $policy);
        $this->assertEquals(0, $policy->from);
        $this->assertEquals(10, $policy->to);
        $this->assertEquals(15, $policy->amount);

        $config4 = ShippingMethodConfiguration::fromArray($this->getFixedPriceRawData(false));
        $this->assertCount(2, $config4->fixedPriceByValuePolicy);
        $policy = $config4->fixedPriceByValuePolicy[1];
        $this->assertInstanceOf('Packlink\BusinessLogic\ShippingMethod\Models\FixedPricePolicy', $policy);
        $this->assertEquals(10, $policy->from);
        $this->assertEquals(22.5, $policy->to);
        $this->assertEquals(32.13, $policy->amount);
    }

    /**
     * Returns raw data that corresponds to Shipping method configuration with Packlink price policy.
     *
     * @return array
     */
    protected function getPacklinkPriceRawData()
    {
        return array(
            'id' => 1,
            'name' => 'Test',
            'showLogo' => true,
            'pricePolicy' => ShippingMethod::PRICING_POLICY_PACKLINK,
        );
    }

    /**
     * Returns raw data that corresponds to Shipping method configuration with Packlink percent price policy.
     *
     * @return array
     */
    protected function getPacklinkPercentPriceRawData()
    {
        return array(
            'id' => 1,
            'name' => 'Test',
            'showLogo' => true,
            'pricePolicy' => ShippingMethod::PRICING_POLICY_PERCENT,
            'percentPricePolicy' => array(
                'amount' => 50,
                'increase' => true,
            ),
        );
    }

    /**
     * Returns raw data that corresponds to Shipping method configuration with fixed price policy.
     *
     * @param bool $byWeight
     *
     * @return array
     */
    protected function getFixedPriceRawData($byWeight)
    {
        $policy = $byWeight ? 'fixedPriceByWeightPolicy' : 'fixedPriceByValuePolicy';
        $pricePolicy = $byWeight ? ShippingMethod::PRICING_POLICY_FIXED_PRICE_BY_WEIGHT
            : ShippingMethod::PRICING_POLICY_FIXED_PRICE_BY_VALUE;
        return array(
            'id' => 1,
            'name' => 'Test',
            'showLogo' => true,
            'pricePolicy' => $pricePolicy,
            $policy => array(
                array(
                    'from' => 0,
                    'to' => 10,
                    'amount' => 15,
                ),
                array(
                    'from' => 10,
                    'to' => 22.5,
                    'amount' => 32.13,
                ),
            ),
        );
    }
}