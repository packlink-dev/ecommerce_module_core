<?php

require_once __DIR__ . '/vendor/leafo/scssphp/scss.inc.php';

buildCss();

function buildCss()
{
    $src = __DIR__ . '/src/BusinessLogic/Resources/scss';
    $dst = __DIR__ . '/src/BusinessLogic/Resources/css';

    $scss = new scssc();
    $scss->setImportPaths('src/BusinessLogic/Resources/scss/');
    $scss->setFormatter('scss_formatter');

    createDir($dst);

    $dstFileName = $dst . '/app.css';
    file_put_contents($dstFileName, '');

    $compiledCss = $scss->compile(file_get_contents("{$src}/app.scss"));
    $existingContent = file_get_contents($dstFileName);
    file_put_contents($dstFileName, $existingContent . $compiledCss);
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
