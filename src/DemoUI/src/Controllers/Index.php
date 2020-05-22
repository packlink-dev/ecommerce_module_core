<?php
namespace Packlink\DemoUI\Controllers;

require_once __DIR__ . '/../../vendor/autoload.php';

$routingController = new ResolverController();
$routingController->handleAction();