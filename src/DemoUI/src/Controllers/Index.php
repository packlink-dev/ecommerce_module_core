<?php
namespace Packlink\DemoUI\Controllers;

use Packlink\DemoUI\Bootstrap;

require_once __DIR__ . '/../../vendor/autoload.php';

session_start();

Bootstrap::init();
$routingController = new ResolverController();
$routingController->handleAction();