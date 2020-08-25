<?php

namespace Packlink\BusinessLogic\Language;

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Language\Interfaces\TranslationService as TranslationServiceInterface;

/**
 * Class Translator.
 *
 * @package Packlink\BusinessLogic\Language
 */
class Translator
{
    /**
     * @var TranslationServiceInterface
     */
    protected static $translationService;

    /**
     * Translates provided string.
     *
     * @param string $key Key to be translated.
     * @param array $arguments List of translation arguments.
     *
     * @return string Translated string.
     */
    public static function translate($key, array $arguments = array())
    {
        return static::getTranslationService()->translate($key, $arguments);
    }

    /**
     * Retrieves translation service.
     *
     * @return TranslationServiceInterface
     */
    protected static function getTranslationService()
    {
        if (static::$translationService === null) {
            static::$translationService = ServiceRegister::getService(TranslationServiceInterface::CLASS_NAME);
        }

        return static::$translationService;
    }
}
