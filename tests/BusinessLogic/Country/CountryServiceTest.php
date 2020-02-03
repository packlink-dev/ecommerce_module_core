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
    /**
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function testGetSupportedCountries()
    {
        /** @var CountryService $service */
        $service = ServiceRegister::getService(CountryService::CLASS_NAME);

        $countries = $service->getSupportedCountries();

        $this->assertNotEmpty($countries);
        $this->assertCount(11, $countries);
        $this->assertEquals('Spain', $countries[0]->name);
        $this->assertEquals('ES', $countries[0]->code);
        $this->assertEquals('28001', $countries[0]->postalCode);
    }
}
