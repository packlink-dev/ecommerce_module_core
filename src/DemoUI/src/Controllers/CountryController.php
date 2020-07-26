<?php

namespace Packlink\DemoUI\Controllers;

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Country\CountryService;

class CountryController extends BaseHttpController
{
    /**
     * @var bool
     */
    protected $requiresAuthentication = false;

    /**
     * Returns list of Packlink supported countries.
     */
    public function get()
    {
        /** @var CountryService $countryService */
        $countryService = ServiceRegister::getService(CountryService::CLASS_NAME);
        $supportedCountries = $countryService->getSupportedCountries(false);

        $this->outputDtoEntities($supportedCountries);
    }

    /**
     * Returns list of Packlink supported countries.
     */
    public function getShippingCountries()
    {
        /** @var CountryService $countryService */
        $countryService = ServiceRegister::getService(CountryService::CLASS_NAME);
        $result = array();

        /**
         * @var string $code
         * @var \Packlink\BusinessLogic\Country\Country $country
         */
        foreach ($countryService->getSupportedCountries() as $code => $country) {
            $result[] = array('value' => $code, 'label' => $country->name);
        }

        $this->output($result);
    }
}