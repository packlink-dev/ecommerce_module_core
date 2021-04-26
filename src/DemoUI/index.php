<?php

use Packlink\DemoUI\Bootstrap;

require_once __DIR__ . "/vendor/autoload.php";

Bootstrap::init();

$brandPlatformCode = getenv('PL_PLATFORM');
require_once __DIR__ . "/src/Views/" . $brandPlatformCode . "/index.php";