<?php

namespace Logeecom\Tests\BusinessLogic\Country;

use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\TestCountry;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\TestFrontDtoFactory;
use Logeecom\Tests\BusinessLogic\Dto\BaseDtoTest;
use Packlink\BusinessLogic\Country\Models\Country;

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
            ),
            'nl' => array(
                'name' => 'Netherlands',
                'code' => 'NL',
                'postal_code' => '1011',
            ),
        );

        /** @var Country[] $countries */
        $countries = TestFrontDtoFactory::getFromBatch(Country::CLASS_KEY, $data);
        $this->assertCount(2, $countries);

        $this->assertEquals('Germany', $countries['de']->name);
        $this->assertEquals('DE', $countries['de']->code);
        $this->assertEquals('10115', $countries['de']->postalCode);

        $this->assertEquals('Netherlands', $countries['nl']->name);
        $this->assertEquals('NL', $countries['nl']->code);
        $this->assertEquals('1011', $countries['nl']->postalCode);
    }

    public function testToArray()
    {
        $country = new TestCountry();

        $data = $country->toArray();
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('code', $data);
        $this->assertArrayHasKey('postal_code', $data);
    }
}
