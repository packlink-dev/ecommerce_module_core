<?php

namespace BusinessLogic\Language;

use Logeecom\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use Logeecom\Infrastructure\Utility\TimeProvider;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\TestShopConfiguration;
use Logeecom\Tests\Infrastructure\Common\TestComponents\Logger\TestShopLogger;
use Logeecom\Tests\Infrastructure\Common\TestComponents\Utility\TestTimeProvider;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Language\TranslationService;
use PHPUnit\Framework\TestCase;

/**
 * Class TranslationServiceTest
 *
 * @package BusinessLogic\Language
 */
class TranslationServiceTest extends TestCase
{
    private $translationService;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();

        $baseFilePath = __DIR__ . '/Translations/';
        $this->translationService = new TranslationService($baseFilePath);

        $configuration = new TestShopConfiguration();
        Configuration::setCurrentLanguage('de');

        new TestServiceRegister(
            array(
                Configuration::CLASS_NAME => function () use ($configuration) {
                    return $configuration;
                }
            )
        );
    }

    /**
     * Tests translation function when non existing key is tried to be translated for not supported language.
     */
    public function testTranslateNotSupportedLanguage()
    {
        $configuration = new TestShopConfiguration();
        Configuration::setCurrentLanguage('rs');
        $logger = new TestShopLogger();
        $timeProvider = new TestTimeProvider();

        new TestServiceRegister(
            array(
                Configuration::CLASS_NAME => function () use ($configuration) {
                    return $configuration;
                },
                TimeProvider::CLASS_NAME => function () use ($timeProvider) {
                    return $timeProvider;
                },
                ShopLoggerAdapter::CLASS_NAME => function () use ($logger) {
                    return $logger;
                }
            )
        );

        $translation = $this->translationService->translate('testKey');

        $this->assertStringStartsWith('testValueEn', $translation);
    }

    /**
     * Tests translation function when non existing key is tried to be translated.
     */
    public function testTranslateNonExistingKey()
    {
        $nonExistingKey = 'noKey';
        $translation = $this->translationService->translate($nonExistingKey);

        $this->assertEquals($nonExistingKey, $translation);
    }

    /**
     * Tests translation function when non existing key in current language is tried to be translated. Key exists in the
     * fallback language.
     */
    public function testTranslateFallbackToEnglish()
    {
        $key = 'testKey1';
        $translation = $this->translationService->translate($key);

        $this->assertEquals('testValueEn1', $translation);
    }

    /**
     * Tests translation function when non existing key is tried to be translated.
     */
    public function testTranslateToGerman()
    {
        $key = 'testKey';
        $translation = $this->translationService->translate($key);

        $this->assertEquals('testValueDe', $translation);
    }

    /**
     * Tests translation function with existing nested key with placeholders.
     */
    public function testTranslateToGermanNestedKeyWithPlaceholders()
    {
        $key = 'namespace_nestedKeyWithPlaceholder';
        $translation = $this->translationService->translate($key, array(1, 2));

        $this->assertEquals('Test1 1, test2 2.', $translation);
    }
}