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
        $supportedCountries = $countryService->getSupportedCountries(false);

        $this->outputDtoEntities($supportedCountries);
    }

    /**
     * Returns list of Packlink supported countries.
     */
    public function getShippingCountries()
    {
        $this->output( array(
            array('value'=> 'de', 'label'=> 'Germany'),
            array('value'=> 'en', 'label'=> 'England'),
            array('value'=> 'es', 'label'=> 'Spain'),
            array('value'=> 'fr', 'label'=> 'France'),
            array('value'=> 'it', 'label'=> 'Italia'),
            array('value'=> 'rs', 'label'=> 'Serbia'),
        ));
    }
}