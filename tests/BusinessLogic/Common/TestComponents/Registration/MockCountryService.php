<?php

namespace Logeecom\Tests\BusinessLogic\Common\TestComponents\Registration;

use Packlink\BusinessLogic\Country\CountryService;

class MockCountryService extends CountryService
{
    protected static $instance;

    public $callHistory = array();
    public static $supportedCountries = array();

    /**
     * Creates instance of this class.
     *
     * @return static
     *
     * @noinspection PhpDocSignatureInspection
     */
    public static function create()
    {
        return new self();
    }

    public function getSupportedCountries($associative = true)
    {
        $this->callHistory[] = array('getSupportedCountries' => array($associative));

        return static::$supportedCountries;
    }
}