<?php

namespace Packlink\BusinessLogic\User;

use Logeecom\Infrastructure\Http\Exceptions\HttpBaseException;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\QueueService;
use Packlink\BusinessLogic\BaseService;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Http\DTO\Analytics;
use Packlink\BusinessLogic\Http\DTO\User;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\Scheduler\Models\DailySchedule;
use Packlink\BusinessLogic\Scheduler\Models\HourlySchedule;
use Packlink\BusinessLogic\Scheduler\Models\Schedule;
use Packlink\BusinessLogic\Scheduler\Models\WeeklySchedule;
use Packlink\BusinessLogic\ShippingMethod\Utility\ShipmentStatus;
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
    protected $configuration;
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
     * Validates provided API key and initializes user's data.
     *
     * @param string $apiKey API key.
     *
     * @return bool TRUE if login went successfully; otherwise, FALSE.
     *
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
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

        $this->createSchedules();

        return true;
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
            if ($this->setParcelInfoInternal()) {
                return;
            }

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
            if ($this->setWarehouseInfoInternal()) {
                return;
            }

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

        $webHookUrl = $this->configuration->getWebHookUrl();
        if (!empty($webHookUrl)) {
            $this->getProxy()->registerWebHookHandler($webHookUrl);
        }

        $this->getProxy()->sendAnalytics(Analytics::EVENT_CONFIGURATION);
    }

    /**
     * Internal method for setting warehouse info in integrations.
     * If integration set it, Core will not fetch the info from Packlink API.
     *
     * @return bool
     */
    protected function setWarehouseInfoInternal()
    {
        return false;
    }

    /**
     * Internal method for setting default parcel info in integrations.
     * If integration set it, Core will not fetch the info from Packlink API.
     *
     * @return bool
     */
    protected function setParcelInfoInternal()
    {
        return false;
    }

    /**
     * Creates schedules.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function createSchedules()
    {
        $repository = RepositoryRegistry::getRepository(Schedule::CLASS_NAME);

        // Schedule weekly task for updating services
        $shippingServicesSchedule = new WeeklySchedule(
            new UpdateShippingServicesTask(),
            $this->configuration->getDefaultQueueName()
        );
        $shippingServicesSchedule->setDay(1);
        $shippingServicesSchedule->setHour(2);
        $shippingServicesSchedule->setNextSchedule();
        $repository->save($shippingServicesSchedule);

        // Schedule hourly task for updating shipment info - start at full hour
        $this->setHourlyTask($repository, 0);

        // Schedule hourly task for updating shipment info - start at half hour
        $this->setHourlyTask($repository, 30);

        // Schedule daily task for updating shipment info - start at 11:00 UTC hour
        $this->setDailyTask($repository, 11);
    }

    /**
     * Creates hourly task for updating shipment data.
     *
     * @param RepositoryInterface $repository Scheduler repository.
     * @param int $minute Starting minute for the task.
     */
    protected function setHourlyTask(RepositoryInterface $repository, $minute)
    {
        $hourlyStatuses = array(
            ShipmentStatus::STATUS_PENDING,
        );

        $shipmentDataHalfHourSchedule = new HourlySchedule(
            new UpdateShipmentDataTask($hourlyStatuses),
            $this->configuration->getDefaultQueueName()
        );
        $shipmentDataHalfHourSchedule->setMinute($minute);
        $shipmentDataHalfHourSchedule->setNextSchedule();
        $repository->save($shipmentDataHalfHourSchedule);
    }

    /**
     * Schedules daily shipment data update task.
     *
     * @param RepositoryInterface $repository Schedule repository.
     * @param int $hour Hour of the day when schedule should be executed.
     */
    protected function setDailyTask(RepositoryInterface $repository, $hour)
    {
        $dailyStatuses = array(
            ShipmentStatus::STATUS_IN_TRANSIT,
            ShipmentStatus::STATUS_READY,
            ShipmentStatus::STATUS_ACCEPTED,
        );

        $dailyShipmentDataSchedule = new DailySchedule(
            new UpdateShipmentDataTask(
                $dailyStatuses
            ),
            $this->configuration->getDefaultQueueName(),
            $this->configuration->getContext()
        );

        $dailyShipmentDataSchedule->setHour($hour);
        $dailyShipmentDataSchedule->setNextSchedule();

        $repository->save($dailyShipmentDataSchedule);
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
