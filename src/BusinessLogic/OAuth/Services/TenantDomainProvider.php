<?php

namespace Packlink\BusinessLogic\OAuth\Services;

use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException;
use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException;
use Packlink\BusinessLogic\Language\Translator;
use Packlink\BusinessLogic\OAuth\Services;

class TenantDomainProvider implements Services\Interfaces\TenantDomainProviderInterface
{
    /**
     * @var array
     */
    private static $TENANT_DOMAINS = array(
        'ES' => 'pro.packlink.es',
        'FR' => 'pro.packlink.fr',
        'DE' => 'pro.packlink.de',
        'IT' => 'pro.packlink.it',
        'WW' => 'pro.packlink.com'
    );


    /**
     * @var string[]
     */
    private static $ALLOWED_COUNTRIES = array(
        'ES', 'FR', 'DE', 'IT', 'WW'
    );

    /**
     * @var string
     */
    private static $DEFAULT_DOMAIN = 'pro.packlink.com';

    /**
     * @param string $tenantCode
     *
     * @return string
     */
    public static function getDomain($tenantCode)
    {
        if (isset(self::$TENANT_DOMAINS[$tenantCode])) {
            return self::$TENANT_DOMAINS[$tenantCode];
        }

        return self::$DEFAULT_DOMAIN;
    }

    /**
     * Returns all allowed country codes.
     *
     * @return array
     * @throws FrontDtoNotRegisteredException
     * @throws FrontDtoValidationException
     */
    public static function getAllowedCountries()
    {
        return self::formatCountries(self::$ALLOWED_COUNTRIES);
    }

    /**
     * Formats country codes to include translated names.
     *
     * @param array $countryCodes
     *
     * @return array
     *
     * @throws FrontDtoNotRegisteredException
     * @throws FrontDtoValidationException
     */

    protected static function formatCountries($countries)
    {
        $formattedCountries = array();

        foreach ($countries as $countryCode) {

            $formattedCountries[] = array(
                'code' => $countryCode,
                'name' => Translator::translate('countries.' . $countryCode)
            );
        }

        return $formattedCountries;
    }
}