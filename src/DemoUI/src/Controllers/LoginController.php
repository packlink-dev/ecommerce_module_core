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
     * Handles login POST request.
     *
     * @param \Packlink\DemoUI\Controllers\Models\Request $request
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function login(Request $request)
    {
        $payload = $request->getPayload();
        $apiKey = !empty($payload['apiKey']) ? $payload['apiKey'] : null;
        $controller = new \Packlink\BusinessLogic\Controllers\LoginController();

        echo json_encode(array('success' => $controller->login($apiKey)));
    }
}