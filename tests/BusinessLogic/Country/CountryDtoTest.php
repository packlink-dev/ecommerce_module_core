<?php

namespace Logeecom\Tests\BusinessLogic\Country;

use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\TestCountry;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\TestFrontDtoFactory;
use Logeecom\Tests\BusinessLogic\Dto\BaseDtoTest;
use Packlink\BusinessLogic\Country\Country;

/**
 * Class CountryDtoTest
 *
 * @package BusinessLogic\Country
 */
class CountryDtoTest extends BaseDtoTest
{
    /**
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoFactoryRegistrationException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
     */
    public function testFromArray()
    {
        TestFrontDtoFactory::register(Country::CLASS_KEY, Country::CLASS_NAME);

        $data = array(
            'de' => array(
                'name' => 'Germany',
                'code' => 'DE',
                'postal_code' => '10115',
                'registration_link' => 'https://pro.packlink.de/registrieren',
            ),
            'nl' => array(
                'name' => 'Netherlands',
                'code' => 'NL',
                'postal_code' => '1011',
                'registration_link' => 'pro.packlink.com/register',
            ),
        );

        /** @var Country[] $countries */
        $countries = TestFrontDtoFactory::getFromBatch(Country::CLASS_KEY, $data);
        $this->assertCount(2, $countries);

        $this->assertEquals('Germany', $countries[0]->name);
        $this->assertEquals('DE', $countries[0]->code);
        $this->assertEquals('10115', $countries[0]->postalCode);
        $this->assertEquals('https://pro.packlink.de/registrieren', $countries[0]->registrationLink);

        $this->assertEquals('Netherlands', $countries[1]->name);
        $this->assertEquals('NL', $countries[1]->code);
        $this->assertEquals('1011', $countries[1]->postalCode);
        $this->assertEquals('pro.packlink.com/register', $countries[1]->registrationLink);
    }

    public function testToArray()
    {
        $country = new TestCountry();

        $data = $country->toArray();
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('code', $data);
        $this->assertArrayHasKey('postal_code', $data);
        $this->assertArrayHasKey('registration_link', $data);
    }
}
