<?php

namespace Packlink\BusinessLogic\Language\Interfaces;

/**
 * Interface TranslationService
 *
 * @package Packlink\BusinessLogic\Language\Interfaces
 */
interface TranslationService
{
    /**
     * Class name.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * Translates a key to a value defined for that key for a current language. If the key can not be found for current
     * language translation fallback value will be returned (translation in English). If the key is not found in fallback
     * passed key will be returned.
     *
     * @param string $key Translation key.
     * @param array $arguments List of translation arguments.
     *
     * @return string Translated string.
     */
    public function translate($key, array $arguments = array());
}