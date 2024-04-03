<?php


namespace Packlink\Brands\Packlink;

use Packlink\BusinessLogic\Brand\BrandConfigurationService;
use Packlink\BusinessLogic\Brand\DTO\BrandConfiguration;

/**
 * Class PacklinkConfigurationService
 *
 * @package Packlink\Brands\Packlink
 */
class PacklinkConfigurationService implements BrandConfigurationService
{
    /**
     * Allowed values for platform countries.
     *
     * @var string[]
     */
    protected static $supportedPlatformCountries = array(
        'UN',
        'ES',
        'DE',
        'FR',
        'IT',
    );
    /**
     * List of supported registration countries.
     *
     * @var array
     */
    protected static $supportedRegistrationCountries = array(
        'ES' => array(
            'code' => 'ES',
            'postal_code' => '28001',
        ),
        'DE' => array(
            'code' => 'DE',
            'postal_code' => '10115',
        ),
        'FR' => array(
            'code' => 'FR',
            'postal_code' => '75001',
        ),
        'IT' => array(
            'code' => 'IT',
            'postal_code' => '00118',
        ),
        'AT' => array(
            'code' => 'AT',
            'postal_code' => '1010',
        ),
        'NL' => array(
            'code' => 'NL',
            'postal_code' => '1011',
        ),
        'BE' => array(
            'code' => 'BE',
            'postal_code' => '1000',
        ),
        'PT' => array(
            'code' => 'PT',
            'postal_code' => '1000-017',
        ),
        'TR' => array(
            'code' => 'TR',
            'postal_code' => '06010',
        ),
        'IE' => array(
            'code' => 'IE',
            'postal_code' => 'D1',
        ),
        'GB' => array(
            'code' => 'GB',
            'postal_code' => 'E1 6AN',
        ),
        'HU' => array(
            'code' => 'HU',
            'postal_code' => '1014',
        ),
    );
    /**
     * List of countries available only for warehouse selection.
     *
     * @var array
     */
    protected static $additionalWarehouseCountries = array(
        'PL' => array(
            'code' => 'PL',
            'postal_code' => '00-694',
        ),
        'CH' => array(
            'code' => 'CH',
            'postal_code' => '3000',
        ),
        'LU' => array(
            'code' => 'LU',
            'postal_code' => '1009',
        ),
        'AR' => array(
            'code' => 'AR',
            'postal_code' => 'C1258 AAA',
        ),
        'US' => array(
            'code' => 'US',
            'postal_code' => '01223',
        ),
        'BO' => array(
            'code' => 'BO',
            'postal_code' => 'La Paz',
        ),
        'MX' => array(
            'code' => 'MX',
            'postal_code' => '21900',
        ),
        'CL' => array(
            'code' => 'CL',
            'postal_code' => '7500599',
        ),
        'CZ' => array(
            'code' => 'CZ',
            'postal_code' => '186 00',
        ),
        'SE' => array(
            'code' => 'SE',
            'postal_code' => '103 16',
        ),
        'GP' => array(
            'code' => 'GP',
            'postal_code' => '97180',
        ),
        'GF' => array(
            'code' => 'GF',
            'postal_code' => '97300',
        ),
        'MQ' => array(
            'code' => 'MQ',
            'postal_code' => '97218',
        ),
        'RE' => array(
            'code' => 'RE',
            'postal_code' => '97410',
        ),
        'YT' => array(
            'code' => 'YT',
            'postal_code' => '97620',
        ),
        'GR' => array(
            'code' => 'GR',
            'postal_code' => '104 31',
        ),
        'FI' => array(
            'code' => 'FI',
            'postal_code' => '00100',
        ),
        'AU' => array(
            'code' => 'AU',
            'postal_code' => '2600',
        ),
        'BG' => array(
            'code' => 'BG',
            'postal_code' => '1000',
        ),
        'EE' => array(
            'code' => 'EE',
            'postal_code' => '10118',
        ),
        'RO' => array(
            'code' => 'RO',
            'postal_code' => '010035',
        ),
        'LV' => array(
            'code' => 'LV',
            'postal_code' => 'LV–1073',
        ),
        'DK' => array(
            'code' => 'DK',
            'postal_code' => '1050',
        ),
        'NO' => array(
            'code' => 'NO',
            'postal_code' => '0010',
        ),
        'SA' => array(
            'code' => 'SA',
            'postal_code' => 'Al-Riyadh',
        ),
        'CA' => array(
            'code' => 'CA',
            'postal_code' => 'K1A 0A1',
        ),
    );

    /**
     * @inheritDoc
     */
    public function get()
    {
        $brandConfiguration = new BrandConfiguration();

        $brandConfiguration->platformCode = 'PRO';
        $brandConfiguration->shippingServiceSource = 'PRO';
        $brandConfiguration->platformCountries = static::$supportedPlatformCountries;
        $brandConfiguration->registrationCountries = static::$supportedRegistrationCountries;
        $brandConfiguration->warehouseCountries = array_merge(
            static::$supportedRegistrationCountries,
            static::$additionalWarehouseCountries
        );

        return $brandConfiguration;
    }
}
