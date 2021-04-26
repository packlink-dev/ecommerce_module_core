<?php

namespace Packlink\DemoUI\Brands\Acme;

use Packlink\BusinessLogic\Brand\BrandConfigurationService;
use Packlink\BusinessLogic\Brand\DTO\BrandConfiguration;

/**
 * Class AcmeConfigurationService
 *
 * @package Packlink\DemoUI\Brands\Acme
 */
class AcmeConfigurationService implements BrandConfigurationService
{

    /**
     * Allowed values for platform countries.
     *
     * @var string[]
     */
    protected static $supportedPlatformCountries = array(
        'ES',
        'DE',
        'FR',
        'IT',
        'UN',
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