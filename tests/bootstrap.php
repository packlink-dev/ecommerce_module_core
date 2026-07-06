<?php

require __DIR__ . '/../vendor/autoload.php';

$isModernPhpUnit = class_exists('PHPUnit\\Runner\\Version')
    && version_compare(\PHPUnit\Runner\Version::series(), '9.0', '>=');

require $isModernPhpUnit
    ? __DIR__ . '/Infrastructure/Common/compat/modern/CompatTestCase.php'
    : __DIR__ . '/Infrastructure/Common/compat/legacy/CompatTestCase.php';
