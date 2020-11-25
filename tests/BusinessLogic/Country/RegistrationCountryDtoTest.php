<?php

namespace BusinessLogic\Country;

use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\TestRegistrationCountry;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\TestFrontDtoFactory;
use Logeecom\Tests\BusinessLogic\Dto\BaseDtoTest;
use Packlink\BusinessLogic\Country\RegistrationCountry;

/**
 * Class RegistrationCountryDtoTest
 *
 * @package BusinessLogic\Country
 */
class RegistrationCountryDtoTest extends BaseDtoTest
{
    /**
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoFactoryRegistrationException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
     */
    public function testFromArray()
    {
        TestFrontDtoFactory::register(RegistrationCountry::CLASS_KEY, RegistrationCountry::CLASS_NAME);

        $data = array(
            'de' => array(
                'name' => 'Germany',
                'code' => 'DE',
                'postal_code' => '10115',
                'registration_link' => 'https://pro.packlink.de/registrieren',
                'platform_country' => 'DE',
            ),
            'nl' => array(
                'name' => 'Netherlands',
                'code' => 'NL',
                'postal_code' => '1011',
                'registration_link' => 'https://pro.packlink.com/register',
                'platform_country' => 'UN',
            ),
        );

        /** @var RegistrationCountry[] $countries */
        $countries = TestFrontDtoFactory::getFromBatch(RegistrationCountry::CLASS_KEY, $data);
        $this->assertCount(2, $countries);

        $this->assertEquals('Germany', $countries['de']->name);
        $this->assertEquals('DE', $countries['de']->code);
        $this->assertEquals('10115', $countries['de']->postalCode);
        $this->assertEquals('https://pro.packlink.de/registrieren', $countries['de']->registrationLink);
        $this->assertEquals('DE', $countries['de']->platformCountry);

        $this->assertEquals('Netherlands', $countries['nl']->name);
        $this->assertEquals('NL', $countries['nl']->code);
        $this->assertEquals('1011', $countries['nl']->postalCode);
        $this->assertEquals('https://pro.packlink.com/register', $countries['nl']->registrationLink);
        $this->assertEquals('UN', $countries['nl']->platformCountry);
    }

    public function testToArray()
    {
        $country = new TestRegistrationCountry();

        $data = $country->toArray();
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('code', $data);
        $this->assertArrayHasKey('postal_code', $data);
        $this->assertArrayHasKey('registration_link', $data);
        $this->assertArrayHasKey('platform_country', $data);
    }
}
