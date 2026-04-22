<?php

namespace Packlink\BusinessLogic\Controllers;

use Exception;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\IntegrationRegistration\Exceptions\IntegrationNotRegisteredException;
use Packlink\BusinessLogic\IntegrationRegistration\Interfaces\IntegrationRegistrationServiceInterface;
use Packlink\BusinessLogic\OAuth\Services\Interfaces\OAuthServiceInterface;
use Packlink\BusinessLogic\User\UserAccountService;
use Packlink\DemoUI\Services\BusinessLogic\ConfigurationService;

/**
 * Class LoginController
 * @package Packlink\BusinessLogic\Controllers
 */
class LoginController
{
    /** @var UserAccountService $userAccountService */
    protected $userAccountService;
    /** @var IntegrationRegistrationServiceInterface $integrationService */
    protected $integrationService;
    /** @var ConfigurationService $configService */
    protected $configService;

    /**
     * LoginController constructor.
     */
    public function __construct($userAccountService, $integrationService, $configService)
    {
        $this->userAccountService = $userAccountService;
        $this->integrationService = $integrationService;
        $this->configService = $configService;
    }

    /**
     * Return flag indicating whether the user is logged in successfully.
     *
     * @param $apiKey
     *
     * @return array 'success' bool, and 'errorCode'
     */
    public function login($apiKey)
    {
        try {
            $result = $this->userAccountService->login($apiKey);

            if (!$result) {
                $this->removeAuthorizationToken();

                return array('success' => false, 'errorCode' => 'invalid_api_key');
            }

            $integrationId = $this->integrationService->registerIntegration();

            if (!$integrationId) {
                $this->handleIntegrationRegistrationFailure();

                return array('success' => false, 'errorCode' => 'integration_registration_failed');
            }

            return array('success' => true, 'errorCode' => null);

        } catch (IntegrationNotRegisteredException $e) {
            $this->handleIntegrationRegistrationFailure();

            return array('success' => false, 'errorCode' => 'integration_registration_failed');
        } catch (Exception $e) {
            $this->removeAuthorizationToken();

            return array('success' => false, 'errorCode' => 'invalid_api_key');
        }
    }

    /**
     * @return string
     */
    public function getRedirectUrl($domain)
    {
        /** @var OAuthServiceInterface $authServiceConfig */
        $authServiceConfig = ServiceRegister::getService(OAuthServiceInterface::CLASS_NAME);
        return $authServiceConfig->buildRedirectUrlAndSaveState($domain);
    }

    /**
     * @return void
     */
    private function removeAuthorizationToken()
    {
        if ($this->configService->getAuthorizationToken() !== null) {
            $this->configService->setAuthorizationToken(null);
        }
    }

    /**
     * @return void
     */
    private function handleIntegrationRegistrationFailure()
    {
        $this->removeAuthorizationToken();
    }
}
