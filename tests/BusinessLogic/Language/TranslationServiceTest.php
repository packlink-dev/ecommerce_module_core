<?php

namespace Logeecom\Tests\BusinessLogic\Language;

use Logeecom\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use Logeecom\Infrastructure\Utility\TimeProvider;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\TestShopConfiguration;
use Logeecom\Tests\Infrastructure\Common\TestComponents\Logger\TestShopLogger;
use Logeecom\Tests\Infrastructure\Common\TestComponents\Utility\TestTimeProvider;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\FileResolver\FileResolverService;
use PHPUnit\Framework\TestCase;

/**
 * Class TranslationServiceTest
 *
 * @package BusinessLogic\Language
 */
class TranslationServiceTest extends TestCase
{
    private static $englishValues = array(
        'rs' => array(),
        'en' => array(
            'testKey' => 'testValueEn 2',
            'testKey1' => 'testValueEn1',
            'testKey2' => 'testValueEn2'
        )
    );
    private static $englishAndFrenchValues = array(
        'fr' => array(
            'testKey' => 'testValueFr',
            'namespace' => array(
                'nestedKeyWithPlaceholder' => 'Nested key fr.'
            )
        ),
        'en' => array(
            'testKey' => 'testValueEn 2',
            'testKey1' => 'testValueEn1',
            'testKey2' => 'testValueEn2'
        ),
    );
    private $translationService;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();

        $fileResolverService = new FileResolverService(
            array(
                __DIR__ . '/Translations',
                __DIR__ . '/../FileResolver/Translations',
            )
        );

        $this->translationService = new TestCountryService($fileResolverService);

        $configuration = new TestShopConfiguration();
        Configuration::setUICountryCode('de');

        new TestServiceRegister(
            array(
                Configuration::CLASS_NAME => function () use ($configuration) {
                    return $configuration;
                }
            )
        );
    }

    /**
     * Tests getText function when current language is not set in config service.
     */
    public function testTranslateCurrentLanguageNotSet()
    {
        $configuration = new TestShopConfiguration();
        Configuration::setUICountryCode(null);
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

        $translation = $this->translationService->getText('testKey');

        $this->assertStringStartsWith('testValueEn', $translation);
    }

    /**
     * Tests getText function when non existing key is tried to be translated for not supported language.
     */
    public function testTranslateNotSupportedLanguage()
    {
        $configuration = new TestShopConfiguration();
        Configuration::setUICountryCode('rs');
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

        $translation = $this->translationService->getText('testKey');

        $this->assertStringStartsWith('testValueEn', $translation);
    }

    /**
     * Tests getText function when non existing key is tried to be translated.
     */
    public function testTranslateNonExistingKey()
    {
        $nonExistingKey = 'noKey';
        $translation = $this->translationService->getText($nonExistingKey);

        $this->assertEquals($nonExistingKey, $translation);
    }

    /**
     * Tests getText function when non existing key in current language is tried to be translated. Key exists in the
     * fallback language.
     */
    public function testTranslateFallbackToEnglish()
    {
        $key = 'testKey1';
        $translation = $this->translationService->getText($key);

        $this->assertEquals('testValueEn1', $translation);
    }

    /**
     * Tests getText function when non existing key is tried to be translated.
     */
    public function testTranslateToGerman()
    {
        $key = 'testKey';
        $translation = $this->translationService->getText($key);

        $this->assertEquals('testValueDe', $translation);
    }

    /**
     * Tests getText function with existing nested key with placeholders.
     */
    public function testTranslateToGermanNestedKeyWithPlaceholders()
    {
        $key = 'namespace.nestedKeyWithPlaceholder';
        $translation = $this->translationService->getText($key, array(1, 2));

        $this->assertEquals('Test1 1, test2 2.', $translation);
    }

    /**
     * Tests getTranslation function with existing language.
     */
    public function testGetTranslations()
    {
        $translations = $this->translationService->getTranslations('fr');

        $this->assertEquals(static::$englishAndFrenchValues, $translations);
    }

    /**
     * Tests getTranslation function with non existing language.
     */
    public function testGetTranslationsWithNonExistingLanguage()
    {
        $translations = $this->translationService->getTranslations('rs');

        $this->assertEquals(static::$englishValues, $translations);
    }
}
