<?php

namespace Logeecom\Tests\BusinessLogic\ShippingMethod;

use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Packlink\BusinessLogic\Controllers\DTO\ShippingMethodConfiguration;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingPricePolicy;

/**
 * Class ShippingMethodConfigurationTest.
 *
 * @package Logeecom\Tests\BusinessLogic\ShippingMethod
 */
class ShippingMethodConfigurationTest extends BaseTestWithServices
{
    /**
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function testShippingMethodFromArray()
    {
        $config = ShippingMethodConfiguration::fromArray($this->getPacklinkPriceRawData());

        $this->assertInstanceOf('Packlink\BusinessLogic\Controllers\DTO\ShippingMethodConfiguration', $config);
        $this->assertEquals(1, $config->id);
        $this->assertEquals('Test', $config->name);
        $this->assertTrue($config->showLogo);
        $this->assertCount(1, $config->pricingPolicies);
        $this->assertFalse($config->usePacklinkPriceIfNotInRange);
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
            'usePacklinkPriceIfNotInRange' => false,
            'pricingPolicies' => array(
                array(
                    'range_type' => ShippingPricePolicy::RANGE_PRICE,
                    'from_price' => 0,
                    'to_price' => 20,
                    'pricing_policy' => ShippingPricePolicy::POLICY_PACKLINK,
                ),
            ),
        );
    }
}