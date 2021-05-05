<?php

namespace Packlink\BusinessLogic\CountryLabels;

use Logeecom\Infrastructure\Configuration\Configuration;
use Packlink\BusinessLogic\FileResolver\FileResolverService;
use Packlink\BusinessLogic\CountryLabels\Interfaces\CountryService as BaseService;

/**
 * Class CountryService
 *
 * @package Packlink\BusinessLogic\Language
 */
class CountryService implements BaseService
{
    /**
     * Default language.
     */
    const DEFAULT_LANG = 'en';

    /**
     * Associative array in format:
     * ['currentLang' => ['labelKey' => 'label']]
     * @var array
     */
    protected static $labels = array();

    /**
     * @var FileResolverService
     */
    protected $fileResolverService;

    /**
     * @var string
     */
    private $currentLanguage;

    /**
     * CountryService constructor.
     *
     * @param FileResolverService $fileResolverService
     */
    public function __construct(FileResolverService $fileResolverService)
    {
        $this->fileResolverService = $fileResolverService;
    }

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
    public function getText($key, array $arguments = array())
    {
        $this->currentLanguage = Configuration::getUICountryCode() ?: static::DEFAULT_LANG;

        $result = $this->fetchLabel($key, static::DEFAULT_LANG);

        return vsprintf($result, $arguments);
    }

    /**
     * @inheritDoc
     */
    public function getLabel($countryCode, $key, $fallbackCode = self::DEFAULT_LANG)
    {
        $languageBackup = $this->currentLanguage;
        $this->currentLanguage = $countryCode;

        $label = $this->fetchLabel($key, $fallbackCode);

        $this->currentLanguage = $languageBackup;

        return $label;
    }

    /**
     * @inheritDoc
     */
    public function getAllLabels($countryCode)
    {
        $labels[$countryCode] = $this->fileResolverService->getContent($countryCode);
        $labels[static::DEFAULT_LANG] = $this->fileResolverService->getContent(static::DEFAULT_LANG);

        return $labels;
    }

    /**
     * @param $key
     * @param $defaultLanguage
     *
     * @return string|null
     */
    protected function fetchLabel($key, $defaultLanguage)
    {
        $result = $this->getLabelByCurrentLanguage($key);

        if ($result === null) {
            $this->initializeLanguage($defaultLanguage);
            $result = $this->getLabelByKeyAndLanguage($key, $defaultLanguage);
        }

        if ($result === null) {
            $result = $key;
        }

        return $result;
    }

    /**
     * Initializes the labels from a file to in-memory map.
     */
    protected function initializeLabels()
    {
        $languageLowerCase = strtolower($this->currentLanguage);
        $this->initializeLanguage($languageLowerCase);
        $this->initializeFallbackLanguage();
    }

    /**
     * Initializes the language to labels dictionary.
     *
     * @param $language
     */
    protected function initializeLanguage($language)
    {
        $labels = $this->fileResolverService->getContent($language);

        foreach ($labels as $groupKey => $group) {
            if (is_array($group)) {
                foreach ($group as $key => $value) {
                    static::$labels[$language][$groupKey . '.' . $key] = $value;
                }
            } else {
                static::$labels[$language][$groupKey] = $group;
            }
        }
    }

    /**
     * Initializes the fallback language.
     */
    protected function initializeFallbackLanguage()
    {
        if (strtolower($this->currentLanguage) !== static::DEFAULT_LANG) {
            $this->initializeLanguage(static::DEFAULT_LANG);
        }
    }

    /**
     * Gets the label for given key and language.
     *
     * @param string $key The label key.
     * @param string $language The language.
     *
     * @return string|null The label.
     */
    protected function getLabelByKeyAndLanguage($key, $language)
    {
        return isset(static::$labels[$language][$key]) ? static::$labels[$language][$key] : null;
    }

    /**
     * Gets label by current language.
     *
     * @param string $key The label key.
     *
     * @return string|null The label.
     */
    protected function getLabelByCurrentLanguage($key)
    {
        if (empty(static::$labels[$this->currentLanguage])) {
            $this->initializeLabels();
        }

        return $this->getLabelByKeyAndLanguage($key, $this->currentLanguage);
    }
}
