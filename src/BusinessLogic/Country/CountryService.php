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
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;
    /**
     * List of supported countries.
     *
     * @var array
     */
    protected static $supportedCountries = array(
        'ES' => array(
            'name' => 'Spain',
            'code' => 'ES',
            'postal_code' => '28001',
        ),
        'DE' => array(
            'name' => 'Germany',
            'code' => 'DE',
            'postal_code' => '10115',
        ),
        'FR' => array(
            'name' => 'France',
            'code' => 'FR',
            'postal_code' => '75001',
        ),
        'IT' => array(
            'name' => 'Italy',
            'code' => 'IT',
            'postal_code' => '00118',
        ),
        'AT' => array(
            'name' => 'Austria',
            'code' => 'AT',
            'postal_code' => '1010',
        ),
        'NL' => array(
            'name' => 'Netherlands',
            'code' => 'NL',
            'postal_code' => '1011',
        ),
        'BE' => array(
            'name' => 'Belgium',
            'code' => 'BE',
            'postal_code' => '1000',
        ),
        'PT' => array(
            'name' => 'Portugal',
            'code' => 'PT',
            'postal_code' => '1000-017',
        ),
        'TR' => array(
            'name' => 'Turkey',
            'code' => 'TR',
            'postal_code' => '06010',
        ),
        'IE' => array(
            'name' => 'Ireland',
            'code' => 'IE',
            'postal_code' => 'D1',
        ),
        'GB' => array(
            'name' => 'United Kingdom',
            'code' => 'GB',
            'postal_code' => 'E1 6AN',
        ),
    );

    /**
     * Returns whether the country with provided ISO code is in a list of supported countries.
     *
     * @param string $isoCode Two-letter country code.
     *
     * @return bool
     */
    public function isCountrySupported($isoCode)
    {
        return array_key_exists($isoCode, self::$supportedCountries);
    }

    /**
     * Returns a list of supported country DTOs.
     *
     * @return \Packlink\BusinessLogic\Country\Country[]
     *
     * @noinspection PhpUnhandledExceptionInspection
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function getSupportedCountries()
    {
        $countries = array();

        foreach (self::$supportedCountries as $country) {
            $countries[$country['code']] = FrontDtoFactory::get(Country::CLASS_KEY, $country);
        }

        return $countries;
    }
}
