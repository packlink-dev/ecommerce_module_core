<?php

namespace Packlink\BusinessLogic\Language;

use Exception;
use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\Logger\Logger;
use Packlink\BusinessLogic\Language\Interfaces\TranslationService as BaseService;

/**
 * Class TranslationService
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
            $currentDir = __DIR__;
            $this->translationsFileBasePath = "{$currentDir}/Translations";
        }
    }

    /**
     * Translates a key to a value defined for that key for a current language. If the key can not be found for current
     * language translation fallback value will be returned (translation in English). If the key is not found in fallback
     * passed key will be returned.
     *
     * @param string $key String to be translated. If the key is nested it should be sent separated by '_'. For example:
     * Translation file has next definition: {"parent": {"child": "childTranslation"}}. Key for parent is: "parent", key
     * for child is: "parent_child"
     * @param array $arguments
     *
     * @return string
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
            static::$translations[$language] = json_decode($serializedJson, true);
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
     * @param string $key
     * @param string $currentLanguage
     *
     * @return string|null
     */
    protected function getTranslation($key, $currentLanguage)
    {
        $keys = explode('_', $key);
        $keysCount = count($keys);

        if ($keysCount > 0 && isset(static::$translations[$currentLanguage])) {
            $result = static::$translations[$currentLanguage];
        } else {
            $result = null;
        }

        foreach ($keys as $i => $value) {
            if (!isset($result[$value]) || (!is_array($result[$value]) && $i < $keysCount - 1)) {
                $result = null;
                break;
            }

            $result = $result[$value];
        }

        return is_string($result) ? $result : null;
    }
}
