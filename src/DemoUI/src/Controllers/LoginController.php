<?php

namespace Packlink\DemoUI\Controllers;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\IntegrationRegistration\Interfaces\IntegrationRegistrationServiceInterface;
use Packlink\BusinessLogic\UpdateShippingServices\Interfaces\UpdateShippingServicesOrchestratorInterface;
use Packlink\BusinessLogic\User\UserAccountService;
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

        $result = $this->getCoreController()->login($apiKey);

        if (!empty($result['success'])) {
            /** @var Configuration $configService */
            $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
            /** @var UpdateShippingServicesOrchestratorInterface $orchestrator */
            $orchestrator = ServiceRegister::getService(UpdateShippingServicesOrchestratorInterface::class);
            $orchestrator->enqueue($configService->getContext());
        }

        $this->output($result);
    }

    public function getRedirectUrl(Request $request)
    {
        $domain = $request->getQuery('domain');

        if (empty($domain)) {
            $domain = 'WW';
        }

        try {
            $this->output(array('redirectUrl' => $this->getCoreController()->getRedirectUrl($domain)));
        } catch (\Throwable $e) {
            $this->output(array('redirect_url' => $e->getMessage(), 'stack_trace' => $e->getTraceAsString()));
        }
    }

    /**
     * Builds the core LoginController with its required dependencies.
     *
     * @return \Packlink\BusinessLogic\Controllers\LoginController
     */
    private function getCoreController()
    {
        /** @var UserAccountService $userAccountService */
        $userAccountService = ServiceRegister::getService(UserAccountService::CLASS_NAME);
        /** @var IntegrationRegistrationServiceInterface $integrationService */
        $integrationService = ServiceRegister::getService(IntegrationRegistrationServiceInterface::CLASS_NAME);
        /** @var Configuration $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);

        return new \Packlink\BusinessLogic\Controllers\LoginController(
            $userAccountService,
            $integrationService,
            $configService
        );
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
