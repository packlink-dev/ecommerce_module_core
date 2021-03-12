<?php

namespace Packlink\BusinessLogic\Language\Interfaces;

/**
 * Interface CountryService
 *
 * @package Packlink\BusinessLogic\Language\Interfaces
 */
interface CountryService
{
    /**
     * Class name.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * Translates a key to a value defined for that key for a current language.
     * If the key can not be found for the current language, translation fallback
     * value will be returned (translation in English).
     * If the key is not found in fallback passed key will be returned.
     *
     * @param string $key <p>String to be translated. If the key is nested it should be sent separated by '.'.
     * For example:
     * Translation file has next definition: {"parent": {"child": "childTranslation"}}.
     * The key for a parent is: "parent", the key for a child is: "parent.child"</p>
     * @param array $arguments A list of arguments for translation if needed.
     *
     * @return string A translated string if translation is found; otherwise, the input key.
     */
    public function getText($key, array $arguments = array());

    /**
     * Fetches translations for a specific country (provided by $countryCode parameter)
     * and default country.
     *
     * @param string $countryCode
     *
     * @return array
     */
    public function getTranslations($countryCode);
}
