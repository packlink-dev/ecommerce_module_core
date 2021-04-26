<?php

namespace Packlink\BusinessLogic\Country;

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\BaseService;
use Packlink\BusinessLogic\Brand\BrandConfigurationService;
use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException;
use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException;
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
        $brand = $this->getBrandConfigurationService()->get();
        $countries = $this->formatCountries($brand->registrationCountries);

        return $associative ? $countries : array_values($countries);
    }

    /**
     * Formats country DTOs.
     *
     * @param $countries
     *
     * @return Country[]
     *
     * @throws FrontDtoNotRegisteredException
     * @throws FrontDtoValidationException
     */
    protected function formatCountries($countries)
    {
        $formattedCountries = array();

        foreach ($countries as $country) {
            $country['name'] = Translator::translate('countries.' . $country['code']);
            $formattedCountries[$country['code']] = FrontDtoFactory::get(Country::CLASS_KEY, $country);
        }

        return $formattedCountries;
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
