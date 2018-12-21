<?php

namespace Logeecom\Tests\Common\TestComponents;

use Logeecom\Infrastructure\Logger\Logger;
use Packlink\BusinessLogic\Configuration;

class TestShopConfiguration extends Configuration
{
    private $token = '';
    private $context = '';
    private $integrationID;
    private $minLogeLevel = Logger::DEBUG;
    private $shopName = 'Unit Test';
    private $publicKey;
    private $secretKey;
    private $baseUrl = 'https://some-shop.test';
    private $callbackUrl = 'https://some-shop.test/callback?a=1&b=abc';
    private $integrationName = 'api';
    private $loggerStatus;
    private $maxStartedTasksLimit = 8;
    private $userInfo;
    private $taskRunnerStatus = '';
    private $servicePointEnabled = true;
    private $carriers = array();

    /**
     * Sets task execution context.
     *
     * When integration supports multiple accounts (middleware integration) proper context must be set based on middleware account
     * that is using core library functionality. This context should then be used by business services to fetch account specific
     * data.Core will set context provided upon task enqueueing before task execution.
     *
     * @param string $context Context to set
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

    /**
     * Gets task execution context
     *
     * @return string Context in which task is being executed. If no context is provided empty string is returned (global context)
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return int
     */
    public function getIntegrationId()
    {
        return $this->integrationID;
    }

    /**
     * @param int $integrationID
     */
    public function setIntegrationId($integrationID)
    {
        $this->integrationID = $integrationID;
    }

    /**
     * Saves min log level in integration database
     *
     * @param int $minLogLevel
     */
    public function saveMinLogLevel($minLogLevel)
    {
        $this->minLogeLevel = $minLogLevel;
    }

    /**
     * Retrieves min log level from integration database
     *
     * @return int
     */
    public function getMinLogLevel()
    {
        return $this->minLogeLevel;
    }

    /**
     * Retrieves integration name
     *
     * @return string
     */
    public function getIntegrationName()
    {
        return $this->integrationName;
    }

    /**
     * Set default logger status (enabled/disabled)
     *
     * @param bool $status
     */
    public function setDefaultLoggerEnabled($status)
    {
        $this->loggerStatus = $status;
    }

    /**
     * Return whether default logger is enabled or not
     *
     * @return bool
     */
    public function isDefaultLoggerEnabled()
    {
        return $this->loggerStatus;
    }

    /**
     * Gets the number of maximum allowed started task at the point in time. This number will determine how many tasks can be
     * in "in_progress" status at the same time
     *
     * @return int
     */
    public function getMaxStartedTasksLimit()
    {
        return $this->maxStartedTasksLimit;
    }

    public function getTaskRunnerWakeupDelay()
    {
        return null;
    }

    public function getTaskRunnerMaxAliveTime()
    {
        return null;
    }

    /**
     * Gets maximum number of failed task execution retries. System will retry task execution in case of error until this number
     * is reached. Return null to use default system value (5)
     *
     * @return int|null
     */
    public function getMaxTaskExecutionRetries()
    {
        return null;
    }

    /**
     * Gets max inactivity period for a task in seconds. After inactivity period is passed, system will fail such tasks as expired.
     * Return null to use default system value (30)
     *
     * @return int|null
     */
    public function getMaxTaskInactivityPeriod()
    {
        return null;
    }

    /**
     * @param array $userInfo
     */
    public function setUserInfo($userInfo)
    {
        $this->userInfo = $userInfo;
    }

    /**
     * @return array
     */
    public function getTaskRunnerStatus()
    {
        return json_decode($this->taskRunnerStatus, true);
    }

    /**
     * Sets task runner status information as JSON encoded string.
     *
     * @param string $guid
     * @param int $timestamp
     */
    public function setTaskRunnerStatus($guid, $timestamp)
    {
        $this->taskRunnerStatus = json_encode(array('guid' => $guid, 'timestamp' => $timestamp));
    }

    /**
     * Resets authorization credentials to null
     */
    public function resetAuthorizationCredentials()
    {
        $this->publicKey = null;
        $this->secretKey = null;
    }

    /**
     * Returns shop base url, if it is sub-shop it should return its specific url
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * Returns callback url, if it is sub-shop it should return its specific url.
     * Urls must have "token" query parameter, and should have "shop_code" if system supports multiple shops.
     *
     * @return string
     */
    public function getCallbackUrl()
    {
        return $this->callbackUrl;
    }

    /**
     * Returns name of current shop.
     *
     * @return string
     */
    public function getShopName()
    {
        return $this->shopName;
    }

    public function setIntegrationName($integrationName)
    {
        $this->integrationName = $integrationName;
    }

    public function setMaxStartedTasksLimit($limit)
    {
        $this->maxStartedTasksLimit = $limit;
    }

    /**
     * Returns service point enabled flag
     *
     * @return bool
     */
    public function isServicePointEnabled()
    {
        return $this->servicePointEnabled;
    }

    /**
     * Sets service point enabled flag
     *
     * @param $enabled
     */
    public function setServicePointEnabled($enabled)
    {
        $this->servicePointEnabled = $enabled;
    }

    /**
     * Returns list of enabled carriers
     *
     * @return array
     */
    public function getCarriers()
    {
        return $this->carriers;
    }

    /**
     * Sets a list of available carriers
     *
     * @param array $carriers
     */
    public function setCarriers(array $carriers = array())
    {
        $this->carriers = $carriers;
    }

    /**
     * Returns scheduler time threshold between checks
     *
     * @return int
     */
    public function getSchedulerTimeThreshold()
    {
        return 60;
    }

    /**
     * Returns scheduler queue name
     *
     * @return string
     */
    public function getSchedulerQueueName()
    {
        return 'Test Scheduler Queue';
    }

    /**
     * Returns current system identifier.
     *
     * @return string Current system identifier.
     */
    public function getCurrentSystemId()
    {
        return '';
    }

    /**
     * Returns authorization token value or null
     *
     * @return string Authorization token value
     */
    public function getAuthorizationToken()
    {
        return $this->token;
    }
}
