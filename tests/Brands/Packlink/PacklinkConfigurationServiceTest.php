<?php

namespace Logeecom\Tests\Brands\Packlink;

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Brand\BrandConfigurationService;

/**
 * Class PacklinkConfigurationServiceTest
 *
 * @package Logeecom\Tests\Brands\Packlink
 */
class PacklinkConfigurationServiceTest extends BaseTestWithServices
{
    public function testGet()
    {
        $service = ServiceRegister::getService(BrandConfigurationService::CLASS_NAME);

        $brandConfiguration = $service->get();
        $this->assertEquals('PRO', $brandConfiguration->platformCode);
        $this->assertEquals('PRO', $brandConfiguration->shippingServiceSource);
        $this->assertCount(5, $brandConfiguration->platformCountries);
        $this->assertEquals('ES', $brandConfiguration->registrationCountries['ES']['code']);
        $this->assertEquals('28001', $brandConfiguration->warehouseCountries['ES']['postal_code']);
    }
}