<?php

namespace Packlink\BusinessLogic\Controllers;

use Exception;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\User\UserAccountService;
use Packlink\DemoUI\Services\BusinessLogic\ConfigurationService;

/**
 * Class LoginController
 * @package Packlink\BusinessLogic\Controllers
 */
class LoginController
{
    /**
     * Return flag indicating whether the user is logged in successfully.
     *
     * @param $apiKey
     *
     * @return bool
     */
    public function login($apiKey)
    {
        $result = false;

        try {
            /** @var UserAccountService $userAccountService */
            $userAccountService = ServiceRegister::getService(UserAccountService::CLASS_NAME);
            $result = $userAccountService->login($apiKey);
        } catch (Exception $e) {
            /** @var ConfigurationService $configService */
            $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
            if ($configService->getAuthorizationToken() !== null) {
                $configService->setAuthorizationToken(null);
            }
        }

        return $result;
    }
}