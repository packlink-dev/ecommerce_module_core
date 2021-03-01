<?php

namespace Packlink\BusinessLogic\Country;

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\BaseService;
use Packlink\BusinessLogic\Brand\BrandConfigurationService;
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
     * BrandConfigurationService instance.
     *
     * @var BrandConfigurationService
     */
    protected $brandConfigurationService;
    /**
     * List of four default countries.
     *
     * @var array
     */
    protected static $baseCountries = array('ES', 'DE', 'FR', 'IT');

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
        $brand = $this->getBrandConfigurationService()->get();

        foreach ($brand->registrationCountries as $country) {
            $country['name'] = Translator::translate('countries.' . $country['code']);
            $countries[$country['code']] = FrontDtoFactory::get(Country::CLASS_KEY, $country);
        }

        return $associative ? $countries : array_values($countries);
    }

    /**
     * Gets BrandConfigurationService.
     *
     * @return BrandConfigurationService
     */
    protected function getBrandConfigurationService()
    {
        if ($this->brandConfigurationService === null) {
            $this->brandConfigurationService = ServiceRegister::getService(BrandConfigurationService::CLASS_NAME);
        }

        return $this->brandConfigurationService;
    }
}
