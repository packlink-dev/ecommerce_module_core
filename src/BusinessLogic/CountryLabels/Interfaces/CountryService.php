<?php

namespace Packlink\BusinessLogic\CountryLabels\Interfaces;

/**
 * Interface CountryService
 *
 * @package Packlink\BusinessLogic\CountryLabels\Interfaces
 */
interface CountryService
{
    /**
     * Class name.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * Gets label for a key in current language.
     * If the key can not be found for the current language, label fallback
     * value will be returned (label in English).
     * If the key is not found in fallback passed key will be returned.
     *
     * @param string $key <p>Key of a wanted label. If the key is nested it should be sent separated by '.'.
     * For example:
     * File that contains labels has next definition: {"parent": {"child": "childLabel"}}.
     * The key for a parent is: "parent", the key for a child is: "parent.child"</p>
     * @param array $arguments A list of arguments.
     *
     * @return string Label if label is found; otherwise, the input key.
     */
    public function getText($key, array $arguments = array());

    /**
     * Fetches labels for a specific country (provided by $countryCode parameter)
     * and default country.
     *
     * @param string $countryCode
     * @param string $key
     * @param string $fallbackCode
     *
     * @return string
     */
    public function getLabel($countryCode, $key, $fallbackCode = '');

    /**
     * Gets all labels for provided country code and default code.
     *
     * @param string $countryCode
     *
     * @return array
     */
    public function getAllLabels($countryCode);
}
