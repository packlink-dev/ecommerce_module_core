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
    /**
     * @throws \Exception
     */
    public function testPdfMerge()
    {
        $pdf = new PdfMerge();

        $pdf->addPDF(__DIR__ . '/../Common/PDF/Brochure.pdf');
        $pdf->addPDF(__DIR__ . '/../Common/PDF/Meeting notes.pdf');
        $file = fopen(__DIR__ . '/../Common/PDF/Merged.pdf', 'rb');
        $expected = fread($file, filesize(__DIR__ . '/../Common/PDF/Merged.pdf'));

        $content = $pdf->merge('Merged.pdf', 'S');
        // date can't
        $content = preg_replace('/CreationDate \(D:\d+\)/', 'CreationDate (D:20190201191519)', $content);

        $this->assertEquals($expected, $content);
    }
}
