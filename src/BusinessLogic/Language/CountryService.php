<?php

namespace Packlink\BusinessLogic\Language;

use Logeecom\Infrastructure\Configuration\Configuration;
use Packlink\BusinessLogic\FileResolver\FileResolverService;
use Packlink\BusinessLogic\Language\Interfaces\CountryService as BaseService;

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
     * ['currentLang' => ['translationKey' => 'translation']]
     * @var array
     */
    protected static $translations = array();

    /**
     * @var FileResolverService
     */
    protected $fileResolverService;

    /**
     * @var string
     */
    private $currentLanguage;

    /**
     * TranslationService constructor.
     *
     * @param FileResolverService $fileResolverService
     */
    public function __construct(FileResolverService $fileResolverService)
    {
        $this->fileResolverService = $fileResolverService;
    }

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
    public function getText($key, array $arguments = array())
    {
        $this->currentLanguage = Configuration::getUICountryCode() ?: static::DEFAULT_LANG;

        if (empty(static::$translations[$this->currentLanguage])) {
            $this->initializeTranslations();
        }

        $result = $this->getTranslation($key, $this->currentLanguage);

        if ($result === null) {
            $result = $this->getTranslation($key, static::DEFAULT_LANG);
        }

        if ($result === null) {
            $result = $key;
        }

        return vsprintf($result, $arguments);
    }

    /**
     * Fetches translations for a specific country (provided by $countryCode parameter)
     * and default country.
     *
     * @param string $countryCode
     *
     * @return array
     */
    public function getTranslations($countryCode)
    {
        $translations[$countryCode] = $this->fileResolverService->getContent($countryCode);
        $translations[static::DEFAULT_LANG] = $this->fileResolverService->getContent(static::DEFAULT_LANG);

        return $translations;
    }

    /**
     * Initializes the translations from a file to in-memory map.
     */
    protected function initializeTranslations()
    {
        $languageLowerCase = strtolower($this->currentLanguage);
        $this->initializeLanguage($languageLowerCase);
        $this->initializeFallbackLanguage();
    }

    /**
     * Initializes the language to translations dictionary.
     *
     * @param $language
     */
    protected function initializeLanguage($language)
    {
        $translations = $this->fileResolverService->getContent($language);

        foreach ($translations as $groupKey => $group) {
            if (is_array($group)) {
                foreach ($group as $key => $value) {
                    static::$translations[$language][$groupKey . '.' . $key] = $value;
                }
            } else {
                static::$translations[$language][$groupKey] = $group;
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
     * Gets the translation for given key and language.
     *
     * @param string $key The translation key.
     * @param string $language The translation language.
     *
     * @return string|null The translation.
     */
    protected function getTranslation($key, $language)
    {
        return isset(static::$translations[$language][$key]) ? static::$translations[$language][$key] : null;
    }
}
