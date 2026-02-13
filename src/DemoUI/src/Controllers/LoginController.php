<?php

namespace Packlink\DemoUI\Controllers;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\UpdateShippingServices\Interfaces\UpdateShippingServicesOrchestratorInterface;
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
     * @var UpdateShippingServicesOrchestratorInterface
     */
    private $updateShippingServicesOrchestrator;
    /**
     * @var bool
     */
    protected $requiresAuthentication = false;

    public function __construct(UpdateShippingServicesOrchestratorInterface $updateShippingServicesOrchestrator)
    {
        $this->updateShippingServicesOrchestrator = $updateShippingServicesOrchestrator;
    }

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
            /** @var Configuration $configService */
            $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
            $this->updateShippingServicesOrchestrator->enqueue($configService->getContext());
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

            $this->output(array('redirectUrl' => $controller->getRedirectUrl($domain)));
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
