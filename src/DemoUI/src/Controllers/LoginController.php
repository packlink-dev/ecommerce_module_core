<?php

namespace Packlink\DemoUI\Controllers;

use Packlink\BusinessLogic\Tasks\UpdateShippingServicesTask;
use Packlink\DemoUI\Controllers\Models\Request;
use Packlink\DemoUI\Services\Integration\UrlService;

/**
 * Class LoginController
 *
 * @package Packlink\DemoUI\Controllers
 */
class LoginController extends BaseHttpController
{
    /**
     * @var bool
     */
    protected $requiresAuthentication = false;

    /**
     * Handles login POST request.
     *
     * @param \Packlink\DemoUI\Controllers\Models\Request $request
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function login(Request $request)
    {
        $payload = $request->getPayload();
        $apiKey = !empty($payload['apiKey']) ? $payload['apiKey'] : null;
        $controller = new \Packlink\BusinessLogic\Controllers\LoginController();

        $success = $controller->login($apiKey);
        if ($success) {
            // this is only for the Demo app because there is no task runner
            $task = new UpdateShippingServicesTask();
            $task->execute();
        }

        $this->output(array('success' => $success));
    }

    public function getRedirectUrl(Request $request)
    {
        $domain = $request->getQuery('domain');

        if (empty($domain)) {
            $domain = 'WW';
        }

        try {
            $controller = new \Packlink\BusinessLogic\Controllers\LoginController();

            $this->output(array('redirect_url' => $controller->getRedirectUrl($domain)));
        } catch (\Throwable $e) {
            $this->output(array('redirect_url' => $e->getMessage(), 'stack_trace' => $e->getTraceAsString()));

        }

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