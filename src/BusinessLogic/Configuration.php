<?php

namespace Packlink\BusinessLogic;

use Packlink\BusinessLogic\DTO\FrontDtoFactory;
use Packlink\BusinessLogic\Http\DTO\ParcelInfo;
use Packlink\BusinessLogic\Http\DTO\User;
use Packlink\BusinessLogic\Warehouse\Warehouse;

/**
 * Class Configuration.
 *
 * @package Packlink\BusinessLogic
 */
abstract class Configuration extends \Logeecom\Infrastructure\Configuration\Configuration
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
     * Default task retention time expressed in days. After this time tasks are not necesseary any more in the system.
     */
    const DEFAULT_MAX_TASK_AGE = 7;
    /**
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;

    /**
     * Retrieves max task age in days. Tasks older than the given number of days are no longer needed in the system.
     *
     * @return int Max task age in days.
     */
    public function getMaxTaskAge()
    {
        return $this->getConfigValue('maxTaskAge', self::DEFAULT_MAX_TASK_AGE);
    }

    /**
     * Sets max task age.
     *
     * @param int $maxAge Positive integer. Denotes max task age in days.
     */
    public function setMaxTaskAge($maxAge)
    {
        $this->saveConfigValue('maxTaskAge', $maxAge);
    }

    /**
     * Returns web-hook callback URL for current system.
     *
     * @return string Web-hook callback URL.
     */
    abstract public function getWebHookUrl();

    /**
     * Returns order draft source.
     *
     * @return string Order draft source.
     */
    abstract public function getDraftSource();

    /**
     * Gets the current version of the module/integration.
     *
     * @return string The version number.
     */
    abstract public function getModuleVersion();

    /**
     * Gets the name of the integrated e-commerce system.
     * This name is related to Packlink API which can be different from the official system name.
     *
     * @return string The e-commerce name.
     */
    abstract public function getECommerceName();

    /**
     * Gets the current version of the integrated e-commerce system.
     *
     * @return string The version number.
     */
    abstract public function getECommerceVersion();

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
     * Sets scheduler time threshold between checks.
     *
     * @param int $schedulerTimeThreshold Threshold in seconds.
     */
    public function setSchedulerTimeThreshold($schedulerTimeThreshold)
    {
        $this->saveConfigValue('schedulerTimeThreshold', $schedulerTimeThreshold);
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
        $value = $this->getConfigValue('userInfo');

        return $value && is_array($value) ? User::fromArray($value) : null;
    }

    /**
     * Save user information in integration database.
     *
     * @param User $userInfo User information.
     */
    public function setUserInfo(User $userInfo)
    {
        $this->saveConfigValue('userInfo', $userInfo->toArray());
    }

    /**
     * Returns default Parcel object.
     *
     * @return ParcelInfo|null Default parcel object.
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function getDefaultParcel()
    {
        $value = $this->getConfigValue('defaultParcel');

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        /** @noinspection PhpUnhandledExceptionInspection */
        return $value && is_array($value) ? FrontDtoFactory::get(ParcelInfo::CLASS_KEY, $value) : null;
    }

    /**
     * Sets default Parcel object.
     *
     * @param ParcelInfo $parcelInfo
     */
    public function setDefaultParcel(ParcelInfo $parcelInfo)
    {
        $this->saveConfigValue('defaultParcel', $parcelInfo->toArray());
    }

    /**
     * Returns default Warehouse object.
     *
     * @return Warehouse|null Default warehouse object.
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function getDefaultWarehouse()
    {
        $value = $this->getConfigValue('defaultWarehouse');

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        /** @noinspection PhpUnhandledExceptionInspection */
        return $value && is_array($value) ? FrontDtoFactory::get(Warehouse::CLASS_KEY, $value) : null;
    }

    /**
     * Sets default Warehouse object.
     *
     * @param Warehouse $warehouse Default warehouse object.
     */
    public function setDefaultWarehouse(Warehouse $warehouse)
    {
        $this->saveConfigValue('defaultWarehouse', $warehouse->toArray());
    }

    /**
     * Sets order status mapping configuration.
     *
     * Expected mapping format:
     *
     * [
     *      'shipped' => 1,
     *      'transit' => 7,
     *      ...
     * ]
     *
     * Keys in submitted array are order statuses available on Packlink.
     * Values are system specific order status identifiers.
     *
     * @param array $mappings As described above.
     */
    public function setOrderStatusMappings(array $mappings)
    {
        $this->saveConfigValue('orderStatusMappings', $mappings);
    }

    /**
     * Retrieves order status mappings.
     *
     * @return array | null  Order status mapping configuration.
     */
    public function getOrderStatusMappings()
    {
        return $this->getConfigValue('orderStatusMappings');
    }

    /**
     * Sets a flag that module setup is finished.
     */
    public function setSetupFinished()
    {
        $this->saveConfigValue('setupFinished', true);
    }

    /**
     * Gets a flag about finished setup.
     *
     * @return bool TRUE if setup is marked as finished; otherwise, FALSE.
     */
    public function isSetupFinished()
    {
        return $this->getConfigValue('setupFinished') ?: false;
    }

    /**
     * Determines whether the configuration entry is system specific.
     *
     * @param string $name Configuration entry name.
     *
     * @return bool
     */
    protected function isSystemSpecific($name)
    {
        if ($name === 'maxTaskAge') {
            return false;
        }

        return parent::isSystemSpecific($name);
    }
}
