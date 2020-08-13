<?php

namespace Logeecom\Tests\BusinessLogic\Common\TestComponents\Registration;

use Packlink\BusinessLogic\Country\CountryService;

class MockCountryService extends CountryService
{
    protected static $instance;

    public $callHistory = array();
    public static $supportedCountries = array();

    public function getSupportedCountries($associative = true)
    {
        $this->callHistory[] = array('getSupportedCountries' => array($associative));

        return static::$supportedCountries;
    }
}