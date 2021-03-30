<?php

namespace Logeecom\Tests\BusinessLogic\Country;

use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Packlink\BusinessLogic\Country\CountryService;

/**
 * Class CountryServiceTest
 *
 * @package Logeecom\Tests\BusinessLogic\Country
 */
class CountryServiceTest extends BaseTestWithServices
{
    public function testGetSupportedCountries()
    {
        /** @var CountryService $service */
        $service = ServiceRegister::getService(CountryService::CLASS_NAME);

        $countries = $service->getSupportedCountries();

        $this->assertNotEmpty($countries);
        $this->assertCount(12, $countries);
        $this->assertArrayHasKey('ES', $countries);
        $this->assertEquals('Spain', $countries['ES']->name);
        $this->assertEquals('ES', $countries['ES']->code);
        $this->assertEquals('28001', $countries['ES']->postalCode);
        $this->assertEquals(
            'https://auth.packlink.com/es-ES/test-system/registro?platform=PRO&platform_country=ES',
            $countries['ES']->registrationLink
        );
    }
}
