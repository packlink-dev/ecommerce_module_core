<?php

namespace Packlink\DemoUI\Controllers;

use Packlink\DemoUI\Controllers\Models\Request;

/**
 * Class ShippingMethodsController
 * @package Packlink\DemoUI\Controllers
 */
class ShippingMethodsController
{
    /**
     * @param \Packlink\DemoUI\Controllers\Models\Request $request
     */
    public function getAll(Request $request)
    {
        echo json_encode(array());
    }

    /**
     * @param \Packlink\DemoUI\Controllers\Models\Request $request
     */
    public function getTaskStatus(Request $request)
    {
        echo json_encode(array('status' => 'completed'));
    }

    /**
     * @param \Packlink\DemoUI\Controllers\Models\Request $request
     */
    public function activate(Request $request)
    {

    }

    /**
     * @param \Packlink\DemoUI\Controllers\Models\Request $request
     */
    public function deactivate(Request $request)
    {

    }

    /**
     * @param \Packlink\DemoUI\Controllers\Models\Request $request
     */
    public function save(Request $request)
    {

    }
}