<?php

namespace Packlink\DemoUI\Controllers;

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Country\CountryService;

class CountryController extends BaseHttpController
{
    /**
     * Returns list of Packlink supported countries.
     */
    public function get()
    {
        /** @var CountryService $countryService */
        $countryService = ServiceRegister::getService(CountryService::CLASS_NAME);
        $supportedCountries = $countryService->getSupportedCountries();

        $this->outputDtoEntities($supportedCountries);
    }
}