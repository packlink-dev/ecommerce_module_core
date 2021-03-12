<?php

namespace Logeecom\Tests\BusinessLogic\Language;

use Packlink\BusinessLogic\Language\CountryService;

/**
 * Class TestTranslationService.
 *
 * @package BusinessLogic\Language
 */
class TestCountryService extends CountryService
{
    public function __construct($fileResolverService)
    {
        parent::__construct($fileResolverService);

        // reset translations for each instance.
        static::$translations = array();
    }
}
