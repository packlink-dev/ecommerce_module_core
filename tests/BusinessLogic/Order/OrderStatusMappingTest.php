<?php

namespace Logeecom\Tests\BusinessLogic\Order;

use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;

/**
 * Class OrderStatusMappingTest.
 *
 * @package Logeecom\Tests\BusinessLogic\Order
 */
class OrderStatusMappingTest extends BaseTestWithServices
{
    public function testOrderStatusMappingsConfiguration()
    {
        $this->assertNull($this->shopConfig->getOrderStatusMappings());

        $this->shopConfig->setOrderStatusMappings($this->getMockData());

        $mappings = $this->shopConfig->getOrderStatusMappings();

        $this->assertNotEmpty($mappings);

        $this->assertEquals(1, $mappings['shipped']);
        $this->assertEquals(5, $mappings['transit']);
        $this->assertEquals(2, $mappings['pending']);
    }

    /**
     * Retrieves mock order mappings.
     *
     * @return array
     */
    protected function getMockData()
    {
        return array(
            'shipped' => 1,
            'transit' => 5,
            'pending' => 2,
        );
    }
}