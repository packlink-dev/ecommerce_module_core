<?php

namespace Packlink\BusinessLogic\User;

use Logeecom\Infrastructure\Http\Exceptions\HttpBaseException;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\QueueService;
use Packlink\BusinessLogic\BaseService;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Http\DTO\User;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\Scheduler\Models\HourlySchedule;
use Packlink\BusinessLogic\Scheduler\Models\Schedule;
use Packlink\BusinessLogic\Scheduler\Models\WeeklySchedule;
use Packlink\BusinessLogic\Tasks\UpdateShipmentDataTask;
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
    }

    /**
     * Logs in user with provided API key.
     *
     * @param string $apiKey API key.
     *
     * @return bool TRUE if login went successfully; otherwise, FALSE.
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function login($apiKey)
    {
        if (empty($apiKey)) {
            return false;
        }

        // set token before calling API
        $this->configuration->setAuthorizationToken($apiKey);

        try {
            $userDto = $this->getProxy()->getUserData();
            $this->initializeUser($userDto);
        } catch (HttpBaseException $e) {
            $this->configuration->resetAuthorizationCredentials();
            Logger::logError($e->getMessage());

            return false;
        }

        return $this->createSchedules();
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
            $parcels = $this->getProxy()->getUsersParcelInfo();
            foreach ($parcels as $parcel) {
                if ($parcel->default) {
                    $parcelInfo = $parcel;
                    break;
                }
            }

            if ($parcelInfo !== null) {
                $this->configuration->setDefaultParcel($parcelInfo);
            }
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
            $usersWarehouses = $this->getProxy()->getUsersWarehouses();
            foreach ($usersWarehouses as $usersWarehouse) {
                if ($usersWarehouse->default) {
                    $warehouse = $usersWarehouse;
                    break;
                }
            }

            $userInfo = $this->configuration->getUserInfo();
            if ($userInfo === null) {
                $userInfo = $this->getProxy()->getUserData();
            }

            if ($warehouse !== null && $userInfo !== null && $warehouse->country === $userInfo->country) {
                $this->configuration->setDefaultWarehouse($warehouse);
            }
        }
    }

    /**
     * Initializes user configuration and subscribes web-hook callback.
     *
     * @param User $user User data.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpBaseException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    protected function initializeUser(User $user)
    {
        $this->configuration->setUserInfo($user);
        $defaultQueueName = $this->configuration->getDefaultQueueName();

        /** @var QueueService $queueService */
        $queueService = ServiceRegister::getService(QueueService::CLASS_NAME);

        $this->setDefaultParcel(true);
        $this->setWarehouseInfo(true);

        $queueService->enqueue($defaultQueueName, new UpdateShippingServicesTask(), $this->configuration->getContext());

        $this->getProxy()->registerWebHookHandler($this->configuration->getWebHookUrl());
    }

    /**
     * Creates schedules.
     *
     * @return bool
     */
    protected function createSchedules()
    {
        /** @var Configuration $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        $shippingServicesSchedule = new WeeklySchedule(new UpdateShippingServicesTask());
        $shipmentDataFullHourSchedule = new HourlySchedule(new UpdateShipmentDataTask());
        $shipmentDataHalfHourSchedule = new HourlySchedule(new UpdateShipmentDataTask());

        $shippingServicesSchedule->setQueueName($configService->getDefaultQueueName());
        $shippingServicesSchedule->setDay(1);
        $shippingServicesSchedule->setHour(2);
        $shippingServicesSchedule->setNextSchedule();

        $shipmentDataFullHourSchedule->setQueueName($configService->getDefaultQueueName());
        $shipmentDataHalfHourSchedule->setQueueName($configService->getDefaultQueueName());
        $shipmentDataFullHourSchedule->setMinute(0);
        $shipmentDataHalfHourSchedule->setMinute(30);
        $shipmentDataFullHourSchedule->setNextSchedule();
        $shipmentDataHalfHourSchedule->setNextSchedule();

        try {
            $repository = RepositoryRegistry::getRepository(Schedule::CLASS_NAME);
        } catch (\Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException $e) {
            Logger::logError('Schedule repository not registered.', 'Core');

            return false;
        }

        $repository->save($shippingServicesSchedule);
        $repository->save($shipmentDataFullHourSchedule);
        $repository->save($shipmentDataHalfHourSchedule);

        return true;
    }

    /**
     * Gets Proxy.
     *
     * @return \Packlink\BusinessLogic\Http\Proxy Proxy.
     */
    protected function getProxy()
    {
        if ($this->proxy === null) {
            $this->proxy = ServiceRegister::getService(Proxy::CLASS_NAME);
        }

        return $this->proxy;
    }
}
