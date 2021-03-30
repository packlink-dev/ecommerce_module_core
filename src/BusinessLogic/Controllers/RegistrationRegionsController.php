<?php

namespace Packlink\BusinessLogic\Controllers;

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Country\CountryService;

/**
 * Class RegistrationRegionsController
 *
 * @package Packlink\BusinessLogic\Controllers
 */
class RegistrationRegionsController
{
    /**
     * Country service.
     *
     * @var \Packlink\BusinessLogic\Country\CountryService
     */
    private $service;

    /**
     * RegistrationRegionsController constructor.
     */
    public function __construct()
    {
        $this->service = ServiceRegister::getService(CountryService::CLASS_NAME);
    }

    public function getRegions()
    {
        return $this->service->getSupportedCountries(false);
    }
}