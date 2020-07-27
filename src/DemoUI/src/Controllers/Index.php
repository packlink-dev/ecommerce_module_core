<?php
namespace Packlink\DemoUI\Controllers;

use Packlink\DemoUI\Bootstrap;

require_once __DIR__ . '/../../vendor/autoload.php';

ini_set('session.gc_maxlifetime', 86400);
session_set_cookie_params(86400);
session_start();

Bootstrap::init();
$routingController = new ResolverController();
$routingController->handleAction();