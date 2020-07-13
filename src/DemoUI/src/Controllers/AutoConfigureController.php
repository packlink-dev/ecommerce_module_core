<?php

namespace Packlink\DemoUI\Controllers;

use Packlink\BusinessLogic\Controllers\AutoConfigurationController;
use Packlink\DemoUI\Controllers\Models\Request;

/**
 * Class AutoConfigureController
 * @package Packlink\DemoUI\Controllers
 */
class AutoConfigureController
{
    /**
     * @param \Packlink\DemoUI\Controllers\Models\Request $request
     *
     * @return string (encoded JSON)
     */
    public function start(Request $request)
    {
        $controller = new AutoConfigurationController();

        echo json_encode(array('success' => $controller->start(true)));
    }
}