<?php
namespace Packlink\DemoUI\Controllers;

use Packlink\DemoUI\Bootstrap;

require_once __DIR__ . '/../../vendor/autoload.php';

$bootstrap = new Bootstrap();
Bootstrap::init();
$routingController = new ResolverController();
$routingController->handleAction();