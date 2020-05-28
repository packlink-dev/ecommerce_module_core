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
     * Associative array in format:
     * ['currentLang' => ['translationKey' => 'translation']]
     * @var array
     */
    protected $translations = array();

    /**
     * @var string $translationsFileBasePath
     */
    protected $translationsFileBasePath;

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
        $this->currentLanguage = Configuration::getCurrentLanguage();

        if (empty($this->translations[$this->currentLanguage][$key])) {
            $this->initializeTranslations();
        }

        $result = $this->getTranslation($key, $this->currentLanguage);

        if ($result === null) {
            $result = $this->getTranslation($key, 'en');
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
        $languageUpperCase = strtoupper($this->currentLanguage);

        $this->translationsFileBasePath = rtrim($this->translationsFileBasePath, '/') . '/';
        $translationFilePath = "{$this->translationsFileBasePath}{$languageLowerCase}_{$languageUpperCase}.json";
        $this->initializeLanguage($translationFilePath, $this->currentLanguage);
        $this->initializeFallbackLanguage();
    }

    private function initializeLanguage($translationFilePath, $language)
    {
        try {
            $serializedJson = file_get_contents($translationFilePath);
        } catch (Exception $ex) {
            $serializedJson = false;
            Logger::logWarning($ex->getMessage());
        }

        if ($serializedJson !== false) {
            $this->translations[$language] = json_decode($serializedJson, true);
        }
    }

    private function initializeFallbackLanguage()
    {
        if (strtolower($this->currentLanguage) != 'en') {
            $translationFilePath = "{$this->translationsFileBasePath}en_EN.json";
            $this->initializeLanguage($translationFilePath, 'en');
        }
    }

    private function getTranslation($key, $currentLanguage)
    {
        $keys = explode('_', $key);

        if (count($keys) > 0 && isset($this->translations[$currentLanguage])) {
            $result = $this->translations[$currentLanguage];
        } else {
            $result = null;
        }

        for ($i = 0; $i < count($keys); $i++) {
            if (!isset($result[$keys[$i]])) {
                $result = null;
                break;
            }

            if ($this->isNonIterableElement($result, $keys, $i)) {
                $result = null;
                break;
            }

            $result = $result[$keys[$i]];
        }

        return $result;
    }

    private function isNonIterableElement($currentNamespace, $keys, $i)
    {
        return !is_array($currentNamespace[$keys[$i]]) && $i < count($keys) - 1;
    }
}