<?php


namespace Logeecom\Tests\BusinessLogic\FileResolver;

use Packlink\BusinessLogic\FileResolver\FileResolverService;
use PHPUnit\Framework\TestCase;

/**
 * Class FileResolverServiceTest
 *
 * @package Logeecom\Tests\BusinessLogic\FileResolver
 */
class FileResolverServiceTest extends TestCase
{
    private static $englishValues = array(
        'testKey' => 'testValueEn 2',
        'testKey1' => 'testValueEn1',
        'testKey2' => 'testValueEn2'
    );
    private static $frenchValues = array(
        'testKey' => 'testValueFr',
        'namespace' => array(
            'nestedKeyWithPlaceholder' => 'Nested key fr.'
        )
    );

    /**
     * @var FileResolverService
     */
    private $fileResolverService;

    /**
     * @before
     * @return void
     */
    public function before()
    {
        $this->setUp();

        $this->fileResolverService = new FileResolverService(
            array(
                __DIR__ . '/../CountryLabels/Labels',
                __DIR__ . '/Translations'
            )
        );
    }

    /**
     * Tests getContent function when files exist.
     */
    public function testGetContentFilesExist()
    {
        $content = $this->fileResolverService->getContent('en');

        $this->assertEquals(static::$englishValues, $content);
    }

    /**
     * Tests getContent function when files do not exist.
     */
    public function testGetContentFilesDoNotExist()
    {
        $content = $this->fileResolverService->getContent('rs');

        $this->assertEquals(array(), $content);
    }

    /**
     * Tests getContent function when one file exists and the other one does not exist.
     */
    public function testGetContentOneFileDoesNotExist()
    {
        $content = $this->fileResolverService->getContent('fr');

        $this->assertEquals(static::$frenchValues, $content);
    }

    /**
     * Test addFolder function.
     */
    public function testAddFolder()
    {
        $this->fileResolverService->addFolder(__DIR__);

        $folders = $this->fileResolverService->getFolders();

        $this->assertEquals(
            array(
                __DIR__ . '/../CountryLabels/Labels',
                __DIR__ . '/Translations',
                __DIR__
            ),
            $folders);
    }
}
