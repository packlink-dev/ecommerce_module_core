<?php

namespace Packlink\BusinessLogic\Language;

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\CountryLabels\Interfaces\CountryService as LabelServiceInterface;

/**
 * Class Translator.
 *
 * @package Packlink\BusinessLogic\Language
 */
class Translator
{
    /**
     * @var LabelServiceInterface
     */
    protected static $countryService;

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
        return static::getCountryService()->getText($key, $arguments);
    }

    /**
     * Retrieves translation service.
     *
     * @return LabelServiceInterface
     */
    protected static function getCountryService()
    {
        if (static::$countryService === null) {
            static::$countryService = ServiceRegister::getService(LabelServiceInterface::CLASS_NAME);
        }

        return static::$countryService;
    }
}
