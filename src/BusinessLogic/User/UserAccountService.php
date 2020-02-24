<?php

namespace Packlink\BusinessLogic\User;

use Logeecom\Infrastructure\Http\Exceptions\HttpBaseException;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Infrastructure\TaskExecution\QueueService;
use Packlink\BusinessLogic\BaseService;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Country\CountryService;
use Packlink\BusinessLogic\Http\DTO\Analytics;
use Packlink\BusinessLogic\Http\DTO\User;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\Scheduler\Models\DailySchedule;
use Packlink\BusinessLogic\Scheduler\Models\HourlySchedule;
use Packlink\BusinessLogic\Scheduler\Models\Schedule;
use Packlink\BusinessLogic\Scheduler\Models\WeeklySchedule;
use Packlink\BusinessLogic\Scheduler\ScheduleCheckTask;
use Packlink\BusinessLogic\ShippingMethod\Utility\ShipmentStatus;
use Packlink\BusinessLogic\Tasks\TaskCleanupTask;
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
     * Validates provided API key and initializes user's data.
     *
     * @param string $apiKey API key.
     *
     * @return bool TRUE if login went successfully; otherwise, FALSE.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function login($apiKey)
    {
        if (empty($apiKey)) {
            return false;
        }

        // set token before calling API
        $this->getConfigService()->setAuthorizationToken($apiKey);

        try {
            $userDto = $this->getProxy()->getUserData();
            $this->initializeUser($userDto);
        } catch (HttpBaseException $e) {
            $this->getConfigService()->resetAuthorizationCredentials();
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
        $parcelInfo = $this->getConfigService()->getDefaultParcel();
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
                $this->getConfigService()->setDefaultParcel($parcelInfo);
            }
        }
    }

    /**
     * Sets warehouse information.
     *
     * @param bool $force Force retrieval of warehouse info from Packlink API.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function setWarehouseInfo($force)
    {
        $warehouse = $this->getConfigService()->getDefaultWarehouse();
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

            if ($warehouse !== null) {
                /** @var CountryService $countryService */
                $countryService = ServiceRegister::getService(CountryService::CLASS_NAME);

                if ($countryService->isCountrySupported($warehouse->country)) {
                    $this->getConfigService()->setDefaultWarehouse($warehouse);
                } else {
                    Logger::logWarning('Warehouse country not supported', 'Core');
                }
            }
        }
    }

    /**
     * Initializes user configuration and subscribes web-hook callback.
     *
     * @param User $user User data.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpBaseException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    protected function initializeUser(User $user)
    {
        $this->getConfigService()->setUserInfo($user);
        $defaultQueueName = $this->getConfigService()->getDefaultQueueName();

        /** @var QueueService $queueService */
        $queueService = ServiceRegister::getService(QueueService::CLASS_NAME);

        $this->setDefaultParcel(true);
        $this->setWarehouseInfo(true);

        if ($this->getConfigService()->getDefaultWarehouse() !== null) {
            $queueService->enqueue(
                $defaultQueueName,
                new UpdateShippingServicesTask(),
                $this->getConfigService()->getContext()
            );
        }

        $webHookUrl = $this->getConfigService()->getWebHookUrl();
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

        $this->scheduleUpdateShipmentServicesTask($repository);

        // Schedule hourly task for updating shipment info - start at full hour
        $this->scheduleUpdatePendingShipmentsData($repository, 0);

        // Schedule hourly task for updating shipment info - start at half hour
        $this->scheduleUpdatePendingShipmentsData($repository, 30);

        // Schedule daily task for updating shipment info - start at 11:00 UTC hour
        $this->scheduleUpdateInProgressShipments($repository, 11);

        // schedule hourly queue cleanup
        $this->scheduleTaskCleanup($repository);
    }

    /**
     * @param \Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface $repository
     *
     */
    protected function scheduleUpdateShipmentServicesTask(RepositoryInterface $repository)
    {
        // Schedule weekly task for updating services
        $schedule = new WeeklySchedule(
            new UpdateShippingServicesTask(),
            $this->getConfigService()->getDefaultQueueName(),
            $this->getConfigService()->getContext()
        );

        $schedule->setDay(1);
        $schedule->setHour(2);
        $schedule->setNextSchedule();
        $repository->save($schedule);
    }

    /**
     * Creates hourly task for updating shipment data for pending shipments.
     *
     * @param RepositoryInterface $repository Scheduler repository.
     * @param int $minute Starting minute for the task.
     */
    protected function scheduleUpdatePendingShipmentsData(RepositoryInterface $repository, $minute)
    {
        $hourlyStatuses = array(
            ShipmentStatus::STATUS_PENDING,
        );

        $schedule = new HourlySchedule(
            new UpdateShipmentDataTask($hourlyStatuses),
            $this->getConfigService()->getDefaultQueueName(),
            $this->getConfigService()->getContext()
        );

        $schedule->setMinute($minute);
        $schedule->setNextSchedule();
        $repository->save($schedule);
    }

    /**
     * Creates daily task for updating shipment data for shipments in progress.
     *
     * @param RepositoryInterface $repository Schedule repository.
     * @param int $hour Hour of the day when schedule should be executed.
     */
    protected function scheduleUpdateInProgressShipments(RepositoryInterface $repository, $hour)
    {
        $dailyStatuses = array(
            ShipmentStatus::STATUS_IN_TRANSIT,
            ShipmentStatus::STATUS_READY,
            ShipmentStatus::STATUS_ACCEPTED,
        );

        $schedule = new DailySchedule(
            new UpdateShipmentDataTask($dailyStatuses),
            $this->getConfigService()->getDefaultQueueName(),
            $this->getConfigService()->getContext()
        );

        $schedule->setHour($hour);
        $schedule->setNextSchedule();

        $repository->save($schedule);
    }

    /**
     * Creates hourly task for cleaning up the database queue for completed items.
     *
     * @param RepositoryInterface $repository Scheduler repository.
     */
    protected function scheduleTaskCleanup(RepositoryInterface $repository)
    {
        $schedule = new HourlySchedule(
            new TaskCleanupTask(ScheduleCheckTask::getClassName(), array(QueueItem::COMPLETED), 3600),
            $this->getConfigService()->getDefaultQueueName(),
            $this->getConfigService()->getContext()
        );

        $schedule->setMinute(10);
        $schedule->setNextSchedule();
        $repository->save($schedule);
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

    /**
     * Returns an instance of configuration service.
     *
     * @return \Packlink\BusinessLogic\Configuration Configuration service.
     */
    protected function getConfigService()
    {
        if ($this->configuration === null) {
            $this->configuration = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configuration;
    }
}
