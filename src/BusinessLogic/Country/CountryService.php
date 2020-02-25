<?php

namespace Packlink\BusinessLogic\Country;

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\BaseService;
use Packlink\BusinessLogic\Configuration;
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
            'registration_link' => 'https://auth.packlink.com/es-ES/{system_name}/registro?platform=PRO&platform_country=ES',
        ),
        'DE' => array(
            'name' => 'Germany',
            'code' => 'DE',
            'postal_code' => '10115',
            'registration_link' => 'https://auth.packlink.com/de-DE/{system_name}/registro?platform=PRO&platform_country=DE',
        ),
        'FR' => array(
            'name' => 'France',
            'code' => 'FR',
            'postal_code' => '75001',
            'registration_link' => 'https://auth.packlink.com/fr-FR/{system_name}/registro?platform=PRO&platform_country=FR',
        ),
        'IT' => array(
            'name' => 'Italy',
            'code' => 'IT',
            'postal_code' => '00118',
            'registration_link' => 'https://auth.packlink.com/it-IT/{system_name}/registro?platform=PRO&platform_country=IT',
        ),
        'AT' => array(
            'name' => 'Austria',
            'code' => 'AT',
            'postal_code' => '1010',
            'registration_link' => 'https://auth.packlink.com/de-DE/{system_name}/registro?platform=PRO&platform_country=UN',
        ),
        'NL' => array(
            'name' => 'Netherlands',
            'code' => 'NL',
            'postal_code' => '1011',
            'registration_link' => 'https://auth.packlink.com/nl-NL/{system_name}/registro?platform=PRO&platform_country=UN',
        ),
        'BE' => array(
            'name' => 'Belgium',
            'code' => 'BE',
            'postal_code' => '1000',
            'registration_link' => 'https://auth.packlink.com/en-GB/{system_name}/registro?platform=PRO&platform_country=UN',
        ),
        'PT' => array(
            'name' => 'Portugal',
            'code' => 'PT',
            'postal_code' => '1000-017',
            'registration_link' => 'https://auth.packlink.com/pt-PT/{system_name}/registro?platform=PRO&platform_country=UN',
        ),
        'TR' => array(
            'name' => 'Turkey',
            'code' => 'TR',
            'postal_code' => '06010',
            'registration_link' => 'https://auth.packlink.com/tr-TR/{system_name}/registro?platform=PRO&platform_country=UN',
        ),
        'IE' => array(
            'name' => 'Ireland',
            'code' => 'IE',
            'postal_code' => 'D1',
            'registration_link' => 'https://auth.packlink.com/en-GB/{system_name}/registro?platform=PRO&platform_country=UN',
        ),
        'GB' => array(
            'name' => 'United Kingdom',
            'code' => 'GB',
            'postal_code' => 'E1 6AN',
            'registration_link' => 'https://auth.packlink.com/en-GB/{system_name}/registro?platform=PRO&platform_country=UN',
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
        $configuration = ServiceRegister::getService(Configuration::CLASS_NAME);

        foreach (self::$supportedCountries as $country) {
            $integration = strtolower($configuration->getIntegrationName());
            $country['registration_link'] = str_replace(
                '{system_name}',
                $integration,
                $country['registration_link']
            );

            $countries[$country['code']] = FrontDtoFactory::get(Country::CLASS_KEY, $country);
        }

        return $countries;
    }
}
