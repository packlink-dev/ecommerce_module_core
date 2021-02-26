<?php

namespace Packlink\BusinessLogic\Country;

use Packlink\BusinessLogic\BaseService;
use Packlink\BusinessLogic\DTO\FrontDtoFactory;
use Packlink\BusinessLogic\Language\Translator;

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
     * List of four default countries.
     *
     * @var array
     */
    protected static $baseCountries = array('ES', 'DE', 'FR', 'IT');
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
        'HU' => array(
            'name' => 'Hungary',
            'code' => 'HU',
            'postal_code' => '1014',
        ),
    );

    /**
     * Checks if given country is one of the four base countries ('ES', 'DE', 'FR', 'IT').
     *
     * @param string $countryCode Country ISO-2 code.
     *
     * @return bool
     */
    public function isBaseCountry($countryCode)
    {
        return in_array(strtoupper($countryCode), static::$baseCountries, true);
    }

    /**
     * Returns a list of supported country DTOs.
     *
     * @param bool $associative Indicates whether the result should be an associative array.
     *
     * @return Country[]
     *
     * @noinspection PhpUnhandledExceptionInspection
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function getSupportedCountries($associative = true)
    {
        $countries = array();

        foreach (static::$supportedCountries as $country) {
            $country['name'] = Translator::translate('countries.' . $country['code']);
            $countries[$country['code']] = FrontDtoFactory::get(Country::CLASS_KEY, $country);
        }

        return $associative ? $countries : array_values($countries);
    }
}
