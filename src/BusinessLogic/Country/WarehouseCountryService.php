<?php

namespace Packlink\BusinessLogic\Country;

use Packlink\BusinessLogic\DTO\FrontDtoFactory;
use Packlink\BusinessLogic\Language\Translator;

/**
 * Class WarehouseCountryService
 *
 * @package Packlink\BusinessLogic\Country
 */
class WarehouseCountryService extends CountryService
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
     * List of countries available only for warehouse selection.
     *
     * @var array
     */
    protected static $additionalWarehouseCountries = array(
        'PL' => array(
            'name' => 'Poland',
            'code' => 'PL',
            'postal_code' => '00-694',
        ),
        'CH' => array(
            'name' => 'Switzerland',
            'code' => 'CH',
            'postal_code' => '3000',
        ),
        'LU' => array(
            'name' => 'Luxembourg',
            'code' => 'LU',
            'postal_code' => '1009',
        ),
        'AR' => array(
            'name' => 'Argentina',
            'code' => 'AR',
            'postal_code' => 'C1258 AAA',
        ),
        'US' => array(
            'name' => 'United States',
            'code' => 'US',
            'postal_code' => '01223',
        ),
        'BO' => array(
            'name' => 'Bolivia',
            'code' => 'BO',
            'postal_code' => 'La Paz',
        ),
        'MX' => array(
            'name' => 'Mexico',
            'code' => 'MX',
            'postal_code' => '21900',
        ),
        'CL' => array(
            'name' => 'Chile',
            'code' => 'CL',
            'postal_code' => '7500599',
        ),
        'CZ' => array(
            'name' => 'Czech Republic',
            'code' => 'CZ',
            'postal_code' => '186 00',
        ),
        'SE' => array(
            'name' => 'Sweden',
            'code' => 'SE',
            'postal_code' => '103 16',
        ),
    );

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
        $countries = array_merge(static::$supportedCountries, static::$additionalWarehouseCountries);

        foreach ($countries as $country) {
            $country['name'] = Translator::translate('countries.' . $country['code']);
            $countries[$country['code']] = FrontDtoFactory::get(Country::CLASS_KEY, $country);
        }

        return $associative ? $countries : array_values($countries);
    }

    /**
     * Returns whether the country with provided ISO code is in a list of supported countries.
     *
     * @param string $isoCode Two-letter country code.
     *
     * @return bool
     */
    public function isCountrySupported($isoCode)
    {
        return array_key_exists($isoCode, array_merge(static::$supportedCountries, static::$additionalWarehouseCountries));
    }
}
