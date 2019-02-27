<?php

namespace Packlink\BusinessLogic\Utility;

use ZendPdf\PdfDocument;

/**
 * Class PdfMerger
 * @package Packlink\BusinessLogic\Utility
 */
class PdfMerge
{
    /**
     * Merges multiple pdf files specified by $pdfPaths array. Returns merged pdf file path.
     *
     * @param array $pdfPaths Array of paths to pdfs.
     * @param string $outputPath Optional specified output path.
     *
     * @return bool | string Returns output file path on success; Returns FALSE otherwise.
     */
    public static function merge(array $pdfPaths, $outputPath = '')
    {
        try {
            $output = PdfDocument::load();

            foreach ($pdfPaths as $path) {
                $pdf = PdfDocument::load($path);
                foreach ($pdf->pages as $page) {
                    $output->pages[] = clone $page;
                }
            }

            $path = $outputPath !== '' ? $outputPath : static::getTempFilePath();

            if (!$path) {
                return false;
            }

            $output->save($path);
        } catch (\Exception $e) {
            return false;
        }

        return $path;
    }

    /**
     * Generates temporary file path.
     *
     * @return bool | string Returns temporary file path. Returns FALSE on failure.
     */
    protected static function getTempFilePath()
    {
        return tempnam(sys_get_temp_dir(), 'bulk_print');
    }
}
