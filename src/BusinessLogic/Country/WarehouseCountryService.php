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
        $countries = $this->getBrandConfigurationService()->get()->warehouseCountries;
        $formattedCountries = $this->formatCountries($countries);

        return $associative ? $formattedCountries : array_values($formattedCountries);
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
        $countries = $this->getBrandConfigurationService()->get()->warehouseCountries;

        return array_key_exists($isoCode, $countries);
    }
}
