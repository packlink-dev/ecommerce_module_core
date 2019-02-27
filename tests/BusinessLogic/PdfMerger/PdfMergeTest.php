<?php

namespace Logeecom\Tests\BusinessLogic\PdfMerger;

use Packlink\BusinessLogic\Utility\PdfMerge;
use PHPUnit\Framework\TestCase;

/**
 * Class PdfMergeTest
 * @package Logeecom\Tests\BusinessLogic\PdfMerger
 */
class PdfMergeTest extends TestCase
{
    public function testPdfMerge()
    {
        $result = PdfMerge::merge($this->getPdfs());
        $this->assertNotFalse($result);
        $this->assertGreaterThan(0, filesize($result));
        $this->assertEquals('application/pdf', mime_content_type($result));
    }

    public function testPdfMergeSpecifiedOutputPath()
    {
        $output = $this->getOutputFilePath();

        $result = PdfMerge::merge($this->getPdfs(), $output);
        $this->assertNotFalse($result);
        $this->assertGreaterThan(0, filesize($result));
        $this->assertEquals('application/pdf', mime_content_type($result));
    }

    public function testFailedMerge()
    {
        $result = PdfMerge::merge(array(__DIR__ . '/../Common/PDF/notAPdf.txt'));
        $this->assertFalse($result);

        $result = PdfMerge::merge($this->getPdfs(), false);
        $this->assertFalse($result);
    }

    protected function tearDown()
    {
        if (file_exists($this->getOutputFilePath())) {
            unlink($this->getOutputFilePath());
        }
    }

    /**
     * Retrieves list of pdfs to be used in tests.
     *
     * @return array
     */
    protected function getPdfs()
    {
        return array(
            __DIR__ . '/../Common/PDF/Brochure.pdf',
            __DIR__ . '/../Common/PDF/Meeting notes.pdf',
        );
    }

    /**
     * Retrieves test output file path.
     *
     * @return string
     */
    protected function getOutputFilePath()
    {
        return __DIR__ . '/../Common/PDF/testOut.pdf';
    }
}
