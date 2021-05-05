<?php

namespace Logeecom\Tests\BusinessLogic\CountryLabels;

use Packlink\BusinessLogic\CountryLabels\CountryService;

/**
 * Class TestCountryService.
 *
 * @package BusinessLogic\Language
 */
class TestCountryService extends CountryService
{
    public function __construct($fileResolverService)
    {
        parent::__construct($fileResolverService);

        // reset translations for each instance.
        static::$labels = array();
    }
}
