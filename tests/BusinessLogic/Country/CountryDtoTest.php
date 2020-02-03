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
                'code' => 'de',
                'postal_code' => '10115',
            ),
            'nl' => array(
                'name' => 'Netherlands',
                'code' => 'nl',
                'postal_code' => '1011',
            ),
        );

        /** @var Country[] $countries */
        $countries = TestFrontDtoFactory::getFromBatch(Country::CLASS_KEY, $data);
        $this->assertCount(2, $countries);

        $this->assertEquals('Germany', $countries[0]->name);
        $this->assertEquals('de', $countries[0]->code);
        $this->assertEquals('10115', $countries[0]->postalCode);

        $this->assertEquals('Netherlands', $countries[1]->name);
        $this->assertEquals('nl', $countries[1]->code);
        $this->assertEquals('1011', $countries[1]->postalCode);
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
