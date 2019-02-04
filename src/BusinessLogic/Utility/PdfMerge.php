<?php

namespace Packlink\BusinessLogic\Utility;

use Exception;
use fpdi\FPDI;
use Logeecom\Infrastructure\Exceptions\BaseException;

/**
 * Class PdfMerger
 * @package Packlink\BusinessLogic\Utility
 */
class PdfMerge
{
    /**
     * List of file paths.
     *
     * @var string[]
     */
    private $files = array();

    /**
     * Add a PDF for inclusion in the merge with a valid file path.
     *
     * @param string $filePath File path.
     *
     * @return PdfMerge Returns this.
     * @throws Exception Thrown when file doesn't exist.
     */
    public function addPDF($filePath)
    {
        if (file_exists($filePath)) {
            $this->files[] = $filePath;
        } else {
            throw new BaseException("File $filePath not found.");
        }

        return $this;
    }

    /**
     * Merges your provided PDFs and outputs to specified location.
     *
     * @param string $outputName Output file name.
     * @param string $openMode Output open mode. D - download, I - inline PDF, S - return as string.
     *
     * @return bool|string Success flag or file content if open mode is "S".
     * @throws Exception
     */
    public function merge($outputName = 'new_file.pdf', $openMode = 'D')
    {
        if (!isset($this->files) || !is_array($this->files)) {
            throw new BaseException('No PDFs to merge.');
        }

        $pdf = new FPDI;
        // merger operations
        foreach ($this->files as $file) {
            $count = $pdf->setSourceFile($file);
            //add the pages

            for ($i = 1; $i <= $count; $i++) {
                $template = $pdf->importPage($i);
                $size = $pdf->getTemplateSize($template);
                $pdf->AddPage('P', array($size['w'], $size['h']));
                $pdf->useTemplate($template);
            }
        }

        if ($openMode === 'S') {
            /** @noinspection PhpUndefinedMethodInspection */
            return $pdf->Output($outputName, $openMode);
        }

        /** @noinspection PhpUndefinedMethodInspection */
        return '' === $pdf->Output($outputName, $openMode);
    }
}
