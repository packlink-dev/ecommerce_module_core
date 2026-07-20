<?php

require __DIR__ . '/../vendor/autoload.php';

// CompatTestCase (legacy/modern variants) is excluded from the classmap and is
// only loadable through the explicit require below - do not rely on autoloading it.
$isModernPhpUnit = class_exists('PHPUnit\\Runner\\Version')
    && version_compare(\PHPUnit\Runner\Version::series(), '9.0', '>=');

require $isModernPhpUnit
    ? __DIR__ . '/Infrastructure/Common/compat/modern/CompatTestCase.php'
    : __DIR__ . '/Infrastructure/Common/compat/legacy/CompatTestCase.php';
