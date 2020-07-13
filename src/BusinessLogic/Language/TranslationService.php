<?php

namespace Packlink\BusinessLogic\Language;

use Exception;
use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\Logger\Logger;
use Packlink\BusinessLogic\Language\Interfaces\TranslationService as BaseService;

/**
 * Class TranslationService.
 *
 * @package Packlink\BusinessLogic\Language
 */
class TranslationService implements BaseService
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
     * @var string $translationsFileBasePath
     */
    protected $translationsFileBasePath;

    /**
     * @var string
     */
    private $currentLanguage;

    /**
     * TranslationService constructor.
     *
     * @param string|null $translationsFileBasePath
     */
    public function __construct($translationsFileBasePath = null)
    {
        $this->translationsFileBasePath = $translationsFileBasePath;

        if (empty($this->translationsFileBasePath)) {
            $this->translationsFileBasePath = __DIR__ . '/../Resources/lang';
        }
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
    public function translate($key, array $arguments = array())
    {
        $this->currentLanguage = Configuration::getCurrentLanguage() ?: static::DEFAULT_LANG;

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
     * Initializes the translations from a file to in-memory map.
     */
    protected function initializeTranslations()
    {
        $languageLowerCase = strtolower($this->currentLanguage);
        $this->translationsFileBasePath = rtrim($this->translationsFileBasePath, '/') . '/';
        $translationFilePath = "{$this->translationsFileBasePath}{$languageLowerCase}.json";
        $this->initializeLanguage($translationFilePath, $this->currentLanguage);
        $this->initializeFallbackLanguage();
    }

    /**
     * Initializes the language to translations dictionary.
     *
     * @param $translationFilePath
     * @param $language
     */
    protected function initializeLanguage($translationFilePath, $language)
    {
        try {
            $serializedJson = file_get_contents($translationFilePath);
        } catch (Exception $ex) {
            $serializedJson = false;
            Logger::logWarning($ex->getMessage());
        }

        if ($serializedJson !== false) {
            $translations = json_decode($serializedJson, true);
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
    }

    /**
     * Initializes the fallback language.
     */
    protected function initializeFallbackLanguage()
    {
        if (strtolower($this->currentLanguage) !== static::DEFAULT_LANG) {
            $defaultLang = static::DEFAULT_LANG;
            $translationFilePath = "{$this->translationsFileBasePath}{$defaultLang}.json";
            $this->initializeLanguage($translationFilePath, static::DEFAULT_LANG);
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
