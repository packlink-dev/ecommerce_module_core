<?php

use Packlink\BusinessLogic\Configuration;
use Packlink\DemoUI\Bootstrap;
use Packlink\DemoUI\Controllers\ResolverController;

require_once __DIR__ . '/../../vendor/autoload.php';

ini_set('session.gc_maxlifetime', 86400);
session_set_cookie_params(86400);
session_start();

Bootstrap::init();
Configuration::setUICountryCode('en');
$routingController = new ResolverController();
$routingController->handleAction();