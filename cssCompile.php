<?php

require_once __DIR__ . '/vendor/leafo/scssphp/scss.inc.php';

$fromBase = __DIR__ . '/src/BusinessLogic/Resources/';
$toBase = __DIR__ . '/src/BusinessLogic/Resources/';

$map = array(
    $fromBase . 'scss' => $toBase . 'css',
);

foreach ($map as $from => $to) {
    buildCss($from, $to);
}

/**
 * Copies directory.
 *
 * @param string $src
 * @param string $dst
 */
function buildCss($src, $dst)
{
    $scss = new scssc();

    createDir($dst);

    $scssFiles = glob("{$src}/*.scss");
    $dstFileName = $dst . '/output.css';
    file_put_contents($dstFileName, '');

    foreach ($scssFiles as $filename) {
        $compiledCss = $scss->compile(file_get_contents($filename));
        $existingContent = file_get_contents($dstFileName);
        file_put_contents($dstFileName,  $existingContent . $compiledCss);
    }
}

/**
 * Creates directory.
 *
 * @param string $destination
 */
function createDir($destination)
{
    if (!file_exists($destination) && !mkdir($destination) && !is_dir($destination)) {
        throw new RuntimeException(sprintf('Directory "%s" was not created', $destination));
    }
}
