<?php

namespace Packlink\DemoUI\Controllers;

use Packlink\DemoUI\Controllers\Models\Request;
use Packlink\DemoUI\Services\Integration\UrlService;

/**
 * Class LoginController
 * @package Packlink\DemoUI\Controllers
 */
class LoginController extends BaseHttpController
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

        $this->output(array('success' => $controller->login($apiKey)));
    }

    /**
     * Terminates the session.
     */
    public function logout()
    {
        session_destroy();

        http_response_code(302);
        header('Location: ' . UrlService::getHomepage());
    }
}