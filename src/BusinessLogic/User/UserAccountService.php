<?php

namespace Packlink\BusinessLogic\User;

use Logeecom\Infrastructure\Http\Exceptions\HttpBaseException;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Queue;
use Packlink\BusinessLogic\BaseService;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\Tasks\GetDefaultParcelAndWarehouseTask;

/**
 * Class UserAccountService.
 *
 * @package Packlink\BusinessLogic\User
 */
class UserAccountService extends BaseService
{
    /**
     * Fully qualified name of this interface.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Configuration service instance
     *
     * @var Configuration
     */
    private $configuration;
    /**
     * Proxy instance
     *
     * @var Proxy
     */
    private $proxy;

    /**
     * Logs in user with provided API key.
     *
     * @param string $apiKey
     *
     * @return bool TRUE if login went successfully; otherwise, FALSE.
     */
    public function login($apiKey)
    {

        if (empty($apiKey)) {
            return false;
        }

        $result = true;
        // set token before calling API
        $this->getConfigService()->setAuthorizationToken($apiKey);

        try {
            $userDto = $this->getProxy()->getUserData();
            $this->initializeUser($userDto->toArray());
        } catch (HttpBaseException $e) {
            Logger::logError($e->getMessage());
            $result = false;
        }


        return $result;
    }

    /**
     * @noinspection PhpDocMissingThrowsInspection
     *
     * Initializes user configuration and subscribes web-hook callback
     *
     * @param array $user User data.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpBaseException
     */
    protected function initializeUser(array $user)
    {
        $config = $this->getConfigService();
        $config->setUserInfo($user);

        /** @var Queue $queueService */
        $queueService = ServiceRegister::getService(Queue::CLASS_NAME);
        /** @noinspection PhpUnhandledExceptionInspection */
        $queueService->enqueue($config->getDefaultQueueName(), new GetDefaultParcelAndWarehouseTask());
        /** @noinspection PhpUnhandledExceptionInspection */
        $queueService->enqueue($config->getDefaultQueueName(), new GetDefaultParcelAndWarehouseTask());


        $this->getProxy()->registerWebHookHandler($config->getWebHookUrl());
    }

    /**
     * Sets default parcel information
     *
     * @param bool $force Force retrieval of parcel info from Packlink API
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpBaseException
     */
    public function setDefaultParcel($force)
    {
        $parcelInfo = $this->getConfigService()->getDefaultParcel();
        if ($parcelInfo === null || $force) {
            $parcels = $this->getProxy()->getUsersParcelInfo();
            foreach ($parcels as $parcel) {
                if ($parcel->default) {
                    $parcelInfo = $parcel;
                }
            }

            $this->getConfigService()->setDefaultParcel($parcelInfo);
        }
    }

    /**
     * Sets warehouse information
     *
     * @param bool $force Force retrieval of warehouse info from Packlink API
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpBaseException
     */
    public function setWarehouseInfo($force)
    {
        $warehouse = $this->getConfigService()->getDefaultWarehouse();
        if ($warehouse === null || $force) {
            $usersWarehouses = $this->getProxy()->getUsersWarehouses();
            foreach ($usersWarehouses as $usersWarehouse) {
                if ($usersWarehouse->default) {
                    $warehouse = $usersWarehouse;
                }
            }

            $this->getConfigService()->setDefaultWarehouse($warehouse);
        }
    }

    /**
     * Returns configuration service.
     *
     * @return Configuration Configuration service.
     */
    private function getConfigService()
    {
        if ($this->configuration === null) {
            $this->configuration = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configuration;
    }

    /**
     * Returns proxy service.
     *
     * @return Proxy Proxy service.
     */
    private function getProxy()
    {
        if ($this->proxy === null) {
            $this->proxy = ServiceRegister::getService(Proxy::CLASS_NAME);
        }

        return $this->proxy;
    }
}