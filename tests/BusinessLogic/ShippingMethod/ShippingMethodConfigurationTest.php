<?php

namespace Logeecom\Tests\BusinessLogic\ShippingMethod;

use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Packlink\BusinessLogic\Controllers\DTO\ShippingMethodConfiguration;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;

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
            'Packlink\BusinessLogic\Controllers\DTO\PercentPricePolicy',
            $config2->percentPricePolicy
        );
        $this->assertEquals(50, $config2->percentPricePolicy->amount);
        $this->assertEquals(true, $config2->percentPricePolicy->increase);

        $config3 = ShippingMethodConfiguration::fromArray($this->getFixedPriceRawData());
        $this->assertCount(2, $config3->fixedPricePolicy);
        $policy = $config3->fixedPricePolicy[1];
        $this->assertInstanceOf('Packlink\BusinessLogic\Controllers\DTO\FixedPricePolicy', $policy);
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
     * @return array
     */
    protected function getFixedPriceRawData()
    {
        return array(
            'id' => 1,
            'name' => 'Test',
            'showLogo' => true,
            'pricePolicy' => ShippingMethod::PRICING_POLICY_FIXED,
            'fixedPricePolicy' => array(
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