<?php

namespace Packlink\BusinessLogic\User;

use Logeecom\Infrastructure\Http\Exceptions\HttpBaseException;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskExecutorInterface;
use Packlink\BusinessLogic\Brand\BrandConfigurationService;
use Packlink\BusinessLogic\Brand\Exceptions\PlatformCountryNotSupportedByBrandException;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Country\WarehouseCountryService;
use Packlink\BusinessLogic\Http\DTO\Analytics;
use Packlink\BusinessLogic\Http\DTO\User;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\Scheduler\DTO\ScheduleConfig;
use Packlink\BusinessLogic\Scheduler\Interfaces\SchedulerInterface;
use Packlink\BusinessLogic\Tasks\BusinessTasks\UpdateShippingServicesBusinessTask;
use Packlink\BusinessLogic\Tasks\UpdateShippingServicesTask;

/**
 * Class UserAccountService.
 *
 * @package Packlink\BusinessLogic\User
 */
class UserAccountService implements Interfaces\UserAccountServiceInterface
{
    /**
     * Fully qualified name of this interface.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * @var TaskExecutorInterface
     */
    private $taskExecutor;
    /**
     * @var SchedulerInterface
     */
    private $scheduler;

    public function __construct(TaskExecutorInterface $taskExecutor, SchedulerInterface $scheduler)
    {
        $this->taskExecutor = $taskExecutor;
        $this->scheduler = $scheduler;
    }

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
     * BrandConfigurationService instance.
     *
     * @var BrandConfigurationService
     */
    private $brandConfigurationService;

    /**
     * Validates provided API key and initializes user's data.
     *
     * @param string $apiKey API key.
     *
     * @return bool TRUE if login went successfully; otherwise, FALSE.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws PlatformCountryNotSupportedByBrandException
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
                /** @var WarehouseCountryService $countryService */
                $countryService = ServiceRegister::getService(WarehouseCountryService::CLASS_NAME);

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
     * @throws PlatformCountryNotSupportedByBrandException
     */
    protected function initializeUser(User $user)
    {
        $brand = $this->getBrandConfigurationService()->get();

        if (!in_array($user->country, $brand->platformCountries, true)) {
            throw new PlatformCountryNotSupportedByBrandException('Platform country not supported by brand!');
        }

        $this->getConfigService()->setUserInfo($user);
        $taskExecutor = $this->taskExecutor;

        $this->setDefaultParcel(true);
        $this->setWarehouseInfo(true);

        if ($this->getConfigService()->getDefaultWarehouse() !== null) {
            $taskExecutor->enqueue(new UpdateShippingServicesBusinessTask());
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
        $this->scheduleUpdateShipmentServicesTask();
    }

    /**
     * @return void
     */
    protected function scheduleUpdateShipmentServicesTask()
    {
        $this->scheduler->scheduleWeekly(
            new UpdateShippingServicesBusinessTask(),
            new ScheduleConfig(
                rand(1, 7),
                rand(0, 5),
                rand(0, 59)
            )
        );
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

    /**
     * Returns an instance of brand configuration service.
     *
     * @return BrandConfigurationService
     */
    protected function getBrandConfigurationService()
    {
        if ($this->brandConfigurationService === null) {
            $this->brandConfigurationService = ServiceRegister::getService(BrandConfigurationService::CLASS_NAME);
        }

        return $this->brandConfigurationService;
    }
}
