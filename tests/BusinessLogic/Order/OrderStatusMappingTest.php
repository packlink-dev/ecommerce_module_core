<?php

namespace Logeecom\Tests\BusinessLogic\Order;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;

class OrderStatusMappingTest extends BaseTestWithServices
{
    public function testOrderStatusMappingsConfiguration()
    {
        /** @var \Packlink\BusinessLogic\Configuration $configService */
        $configService = TestServiceRegister::getService(
            Configuration::CLASS_NAME
        );

        $this->assertNull($configService->getOrderStatusMappings());

        $configService->setOrderStatusMappings($this->getMockData());

        $mappings = $configService->getOrderStatusMappings();

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