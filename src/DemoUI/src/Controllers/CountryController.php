<?php


namespace Packlink\DemoUI\Controllers;


use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Country\Country;
use Packlink\BusinessLogic\Country\CountryService;

class CountryController
{
    /**
     * Returns list of Packlink supported countries.
     *
     */
    public function get()
    {
        /** @var CountryService $countryService */
        $countryService = ServiceRegister::getService(CountryService::CLASS_NAME);
        $supportedCountries = $countryService->getSupportedCountries();

        echo json_encode($this->returnDtoEntitiesResponse($supportedCountries));
    }

    private function returnDtoEntitiesResponse(array $supportedCountries)
    {
        $response = array();

        /** @var Country $supportedCountry */
        foreach ($supportedCountries as $supportedCountry) {
            $response[] = $supportedCountry->toArray();
        }

        return $response;
    }
}