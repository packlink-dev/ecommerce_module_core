<?php

namespace BusinessLogic\Country;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Packlink\BusinessLogic\Country\WarehouseCountryService;

/**
 * Class WarehouseCountryServiceTest
 *
 * @package BusinessLogic\Country
 */
class WarehouseCountryServiceTest extends BaseTestWithServices
{
    public function testGetSupportedCountries()
    {
        Configuration::setUICountryCode('en');
        /** @var WarehouseCountryService $service */
        $service = ServiceRegister::getService(WarehouseCountryService::CLASS_NAME);

        $countries = $service->getSupportedCountries();

        $this->assertNotEmpty($countries);
        $this->assertCount(40, $countries);
        $this->assertArrayHasKey('MX', $countries);
        $this->assertEquals('Mexico', $countries['MX']->name);
        $this->assertEquals('MX', $countries['MX']->code);
        $this->assertEquals('21900', $countries['MX']->postalCode);
    }

    public function testSupportedCountry()
    {
        /** @var WarehouseCountryService $service */
        $service = ServiceRegister::getService(WarehouseCountryService::CLASS_NAME);

        $this->assertTrue($service->isCountrySupported('CZ'));
    }

    public function testUnsupportedCountry()
    {
        /** @var WarehouseCountryService $service */
        $service = ServiceRegister::getService(WarehouseCountryService::CLASS_NAME);

        $this->assertFalse($service->isCountrySupported('RS'));
    }
}
