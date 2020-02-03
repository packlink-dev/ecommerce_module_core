<?php

namespace Packlink\BusinessLogic\Country;

use Packlink\BusinessLogic\BaseService;
use Packlink\BusinessLogic\DTO\FrontDtoFactory;

/**
 * Class CountryProvider
 *
 * @package Packlink\BusinessLogic\Country
 */
class CountryService extends BaseService
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * List of supported countries.
     *
     * @var array
     */
    private static $supportedCountries = array(
        'es' => array(
            'name' => 'Spain',
            'code' => 'es',
            'postal_code' => '28001',
        ),
        'de' => array(
            'name' => 'Germany',
            'code' => 'de',
            'postal_code' => '10115',
        ),
        'fr' => array(
            'name' => 'France',
            'code' => 'fr',
            'postal_code' => '75000',
        ),
        'it' => array(
            'name' => 'Italy',
            'code' => 'it',
            'postal_code' => '00100',
        ),
        'at' => array(
            'name' => 'Austria',
            'code' => 'at',
            'postal_code' => '1010',
        ),
        'nl' => array(
            'name' => 'Netherlands',
            'code' => 'nl',
            'postal_code' => '1011',
        ),
        'be' => array(
            'name' => 'Belgium',
            'code' => 'be',
            'postal_code' => '1000',
        ),
        'pt' => array(
            'name' => 'Portugal',
            'code' => 'pl',
            'postal_code' => '1000-017',
        ),
        'tr' => array(
            'name' => 'Turkey',
            'code' => 'tr',
            'postal_code' => '06010',
        ),
        'ie' => array(
            'name' => 'Ireland',
            'code' => 'ie',
            'postal_code' => 'D1',
        ),
        'uk' => array(
            'name' => 'United Kingdom',
            'code' => 'uk',
            'postal_code' => 'N0L 1E0',
        ),
    );

    /**
     * Returns a list of supported country DTOs.
     *
     * @return \Packlink\BusinessLogic\Country\Country[]
     *
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function getSupportedCountries()
    {
        $countries = array();

        foreach (self::$supportedCountries as $country) {
            $countries[] = FrontDtoFactory::get(Country::CLASS_KEY, $country);
        }

        return $countries;
    }
}
