<?php

namespace Logeecom\Tests\BusinessLogic\Language;

use Packlink\BusinessLogic\Language\TranslationService;

/**
 * Class TestTranslationService.
 *
 * @package BusinessLogic\Language
 */
class TestTranslationService extends TranslationService
{
    public function __construct($translationsFileBasePath = null)
    {
        parent::__construct($translationsFileBasePath);

        // reset translations for each instance.
        static::$translations = array();
    }
}
