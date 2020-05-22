<?php

namespace Packlink\DemoUI\Controllers;

use Packlink\DemoUI\Controllers\Models\Request;

/**
 * Class LoginController
 * @package Packlink\DemoUI\Controllers
 */
class LoginController
{
    /**
     * @param \Packlink\DemoUI\Controllers\Models\Request $request
     *
     * @return bool
     */
    public function login(Request $request)
    {
        $apiKey = $request->getQuery('api_key');

        return !empty($apiKey);
    }
}