<?php

namespace Packlink\BusinessLogic\Controllers;

use Exception;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Configuration;
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
    /**
     * @var string|null
     */
    protected $lastErrorCode = null;

    /**
     * Return flag indicating whether the user is logged in successfully.
     *
     * @param $apiKey
     *
     * @return bool
     */
    public function login($apiKey)
    {
        try {
            /** @var UserAccountService $userAccountService */
            $userAccountService = ServiceRegister::getService(UserAccountService::CLASS_NAME);
            $result = $userAccountService->login($apiKey);

            if (!$result) {
                $this->removeAuthorizationToken();
                $this->lastErrorCode = 'invalid_api_key';

                return false;
            }

            /** @var IntegrationRegistrationServiceInterface $integrationService */
            $integrationService = ServiceRegister::getService(IntegrationRegistrationServiceInterface::CLASS_NAME);
            $integrationId = $integrationService->registerIntegration();

            if (!$integrationId) {
                $this->handleIntegrationRegistrationFailure();

                return false;
            }

            $this->lastErrorCode = null;

            return true;

        } catch (IntegrationNotRegisteredException $e) {
            $this->handleIntegrationRegistrationFailure();

            return false;
        } catch (Exception $e) {
            $this->removeAuthorizationToken();
            $this->lastErrorCode = 'invalid_api_key';

            return false;
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
        /** @var ConfigurationService $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        if ($configService->getAuthorizationToken() !== null) {
            $configService->setAuthorizationToken(null);
        }
    }

    /**
     * @return void
     */
    private function handleIntegrationRegistrationFailure()
    {
        $this->lastErrorCode = 'integration_registration_failed';
        $this->removeAuthorizationToken();
    }

    /**
     * Error code used for UI error message display
     *
     * @return string|null
     */
    public function getLastErrorCode()
    {
        return $this->lastErrorCode;
    }
}
