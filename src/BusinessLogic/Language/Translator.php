<?php

namespace Packlink\BusinessLogic\Language;

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Language\Interfaces\TranslationService as TranslationServiceInterface;

/**
 * Class Translator
 * @package CleverReach\BusinessLogic\Language
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
        return self::getTranslationService()->translate($key, $arguments);
    }

    /**
     * Retrieves translation service.
     *
     * @return TranslationServiceInterface
     */
    protected static function getTranslationService()
    {
        if (self::$translationService === null) {
            self::$translationService = ServiceRegister::getService(TranslationServiceInterface::CLASS_NAME);
        }

        return self::$translationService;
    }

    /**
     * Resets translation service instance.
     */
    public static function resetInstance()
    {
        static::$translationService = null;
    }
}
