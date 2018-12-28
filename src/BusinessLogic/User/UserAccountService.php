<?php

namespace Packlink\BusinessLogic\User;

use Logeecom\Infrastructure\Http\Exceptions\HttpBaseException;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Queue;
use Packlink\BusinessLogic\BaseService;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Http\DTO\User;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\Tasks\GetDefaultParcelAndWarehouseTask;
use Packlink\BusinessLogic\Tasks\UpdateShippingServicesTask;

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
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;
    /**
     * Configuration service instance.
     *
     * @var Configuration
     */
    private $configuration;
    /**
     * Proxy instance.
     *
     * @var Proxy
     */
    private $proxy;

    /**
     * UserAccountService constructor.
     */
    protected function __construct()
    {
        parent::__construct();

        $this->configuration = ServiceRegister::getService(Configuration::CLASS_NAME);
        $this->proxy = ServiceRegister::getService(Proxy::CLASS_NAME);
    }

    /**
     * Logs in user with provided API key.
     *
     * @param string $apiKey API key.
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
        $this->configuration->setAuthorizationToken($apiKey);

        try {
            $userDto = $this->proxy->getUserData();
            $this->initializeUser($userDto);
        } catch (HttpBaseException $e) {
            Logger::logError($e->getMessage());
            $result = false;
        }

        return $result;
    }

    /**
     * Sets default parcel information.
     *
     * @param bool $force Force retrieval of parcel info from Packlink API.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpBaseException
     */
    public function setDefaultParcel($force)
    {
        $parcelInfo = $this->configuration->getDefaultParcel();
        if ($parcelInfo === null || $force) {
            $parcels = $this->proxy->getUsersParcelInfo();
            foreach ($parcels as $parcel) {
                if ($parcel->default) {
                    $parcelInfo = $parcel;
                    break;
                }
            }

            $this->configuration->setDefaultParcel($parcelInfo);
        }
    }

    /**
     * Sets warehouse information.
     *
     * @param bool $force Force retrieval of warehouse info from Packlink API.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpBaseException
     */
    public function setWarehouseInfo($force)
    {
        $warehouse = $this->configuration->getDefaultWarehouse();
        if ($warehouse === null || $force) {
            $usersWarehouses = $this->proxy->getUsersWarehouses();
            foreach ($usersWarehouses as $usersWarehouse) {
                if ($usersWarehouse->default) {
                    $warehouse = $usersWarehouse;
                    break;
                }
            }

            $this->configuration->setDefaultWarehouse($warehouse);
        }
    }

    /**
     * @noinspection PhpDocMissingThrowsInspection
     *
     * Initializes user configuration and subscribes web-hook callback.
     *
     * @param User $user User data.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpBaseException
     */
    protected function initializeUser(User $user)
    {
        $this->configuration->setUserInfo($user);
        $defaultQueueName = $this->configuration->getDefaultQueueName();

        /** @var Queue $queueService */
        $queueService = ServiceRegister::getService(Queue::CLASS_NAME);
        /** @noinspection PhpUnhandledExceptionInspection */
        $queueService->enqueue($defaultQueueName, new GetDefaultParcelAndWarehouseTask());
        /** @noinspection PhpUnhandledExceptionInspection */
        $queueService->enqueue($defaultQueueName, new UpdateShippingServicesTask());

        $this->proxy->registerWebHookHandler($this->configuration->getWebHookUrl());
    }
}
