<?php

namespace Logeecom\Tests\BusinessLogic\CountryLabels;

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
 * Class CountryLabelsServiceTest
 *
 * @package BusinessLogic\Language
 */
class CountryLabelsServiceTest extends TestCase
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
    private $testCountryService;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();

        $fileResolverService = new FileResolverService(
            array(
                __DIR__ . '/Labels',
                __DIR__ . '/../FileResolver/Translations',
            )
        );

        $this->testCountryService = new TestCountryService($fileResolverService);

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

        $translation = $this->testCountryService->getText('testKey');

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

        $translation = $this->testCountryService->getText('testKey');

        $this->assertStringStartsWith('testValueEn', $translation);
    }

    /**
     * Tests getText function when non existing key is tried to be translated.
     */
    public function testTranslateNonExistingKey()
    {
        $nonExistingKey = 'noKey';
        $translation = $this->testCountryService->getText($nonExistingKey);

        $this->assertEquals($nonExistingKey, $translation);
    }

    /**
     * Tests getText function when non existing key in current language is tried to be translated. Key exists in the
     * fallback language.
     */
    public function testTranslateFallbackToEnglish()
    {
        $key = 'testKey1';
        $translation = $this->testCountryService->getText($key);

        $this->assertEquals('testValueEn1', $translation);
    }

    /**
     * Tests getText function when non existing key is tried to be translated.
     */
    public function testTranslateToGerman()
    {
        $key = 'testKey';
        $translation = $this->testCountryService->getText($key);

        $this->assertEquals('testValueDe', $translation);
    }

    /**
     * Tests getText function with existing nested key with placeholders.
     */
    public function testTranslateToGermanNestedKeyWithPlaceholders()
    {
        $key = 'namespace.nestedKeyWithPlaceholder';
        $translation = $this->testCountryService->getText($key, array(1, 2));

        $this->assertEquals('Test1 1, test2 2.', $translation);
    }

    /**
     * Tests getAllLabels.
     */
    public function testGetAllLabels()
    {
        $translations = $this->testCountryService->getAllLabels('fr');

        $this->assertEquals(static::$englishAndFrenchValues, $translations);
    }

    /**
     * Tests getAllLabels with non existing language.
     */
    public function testGetLabelsWithNonExistingLanguage()
    {
        $translations = $this->testCountryService->getAllLabels('rs');

        $this->assertEquals(static::$englishValues, $translations);
    }

    /**
     * Tests getLabels function with existing key with existing language.
     */
    public function testGetLabelsWithKey()
    {
        $label = $this->testCountryService->getLabel('fr', 'testKey');

        $this->assertEquals('testValueFr', $label);
    }

    /**
     * Tests getLabels function with non exiting key.
     */
    public function testGetLabelsWithNonExistingKey()
    {
        $label = $this->testCountryService->getLabel('fr', 'testKeyNonExisting');

        $this->assertEquals('testKeyNonExisting', $label);
    }

    public function testGetLabelsWithFallbackCountry()
    {
        $label = $this->testCountryService->getLabel('en', 'namespace.nestedKeyWithPlaceholder', 'fr');

        $this->assertEquals('Nested key fr.', $label);
    }
}
