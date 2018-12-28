<?php

namespace Packlink\BusinessLogic;

use Packlink\BusinessLogic\Http\DTO\ParcelInfo;
use Packlink\BusinessLogic\Http\DTO\User;
use Packlink\BusinessLogic\Http\DTO\Warehouse;

/**
 * Class Configuration.
 *
 * @package Packlink\BusinessLogic
 */
abstract class Configuration extends \Logeecom\Infrastructure\Configuration
{
    /**
     * Threshold between two runs of scheduler.
     */
    const SCHEDULER_TIME_THRESHOLD = 60;
    /**
     * Default scheduler queue name.
     */
    const DEFAULT_SCHEDULER_QUEUE_NAME = 'SchedulerCheckTaskQueue';
    /**
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;

    /**
     * Returns web-hook callback URL for current system.
     *
     * @return string Web-hook callback URL.
     */
    abstract public function getWebHookUrl();

    /**
     * Returns scheduler time threshold between checks.
     *
     * @return int Threshold in seconds.
     */
    public function getSchedulerTimeThreshold()
    {
        return $this->getConfigValue('schedulerTimeThreshold', static::SCHEDULER_TIME_THRESHOLD);
    }

    /**
     * Returns scheduler queue name.
     *
     * @return string Queue name.
     */
    public function getSchedulerQueueName()
    {
        return $this->getConfigValue('schedulerQueueName', static::DEFAULT_SCHEDULER_QUEUE_NAME);
    }

    /**
     * Returns authorization token.
     *
     * @return string|null Authorization token if found; otherwise, NULL.
     */
    public function getAuthorizationToken()
    {
        return $this->getConfigValue('authToken') ?: null;
    }

    /**
     * Sets authorization token.
     *
     * @param string $token Authorization token.
     */
    public function setAuthorizationToken($token)
    {
        $this->saveConfigValue('authToken', $token);
    }

    /**
     * Resets authorization credentials to null.
     */
    public function resetAuthorizationCredentials()
    {
        $this->setAuthorizationToken(null);
    }

    /**
     * Returns user information from integration database.
     *
     * @return User|null User info.
     */
    public function getUserInfo()
    {
        return $this->getConfigValue('userInfo');
    }

    /**
     * Save user information in integration database.
     *
     * @param User $userInfo User information.
     */
    public function setUserInfo(User $userInfo)
    {
        $this->saveConfigValue('userInfo', $userInfo);
    }

    /**
     * Returns default Parcel object.
     *
     * @return ParcelInfo|null Default parcel object.
     */
    public function getDefaultParcel()
    {
        return $this->getConfigValue('defaultParcel');
    }

    /**
     * Sets default Parcel object.
     *
     * @param ParcelInfo $parcelInfo
     */
    public function setDefaultParcel(ParcelInfo $parcelInfo)
    {
        $this->saveConfigValue('defaultParcel', $parcelInfo);
    }

    /**
     * Returns default Warehouse object.
     *
     * @return Warehouse|null Default warehouse object.
     */
    public function getDefaultWarehouse()
    {
        return $this->getConfigValue('defaultWarehouse');
    }

    /**
     * Sets default Warehouse object.
     *
     * @param Warehouse $warehouse Default warehouse object.
     */
    public function setDefaultWarehouse(Warehouse $warehouse)
    {
        $this->saveConfigValue('defaultWarehouse', $warehouse);
    }
}
