<?php

namespace Packlink\DemoUI\Controllers;

use Packlink\DemoUI\Controllers\Models\Request;

/**
 * Class DashboardController
 * @package Packlink\DemoUI\Controllers
 */
class DashboardController
{
    /**
     * @param \Packlink\DemoUI\Controllers\Models\Request $request
     *
     * @return string (encoded JSON)
     */
    public function getStatus(Request $request)
    {
        echo json_encode($request);
    }
}