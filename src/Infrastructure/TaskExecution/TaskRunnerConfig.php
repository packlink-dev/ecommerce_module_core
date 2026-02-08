<?php

namespace Logeecom\Infrastructure\TaskExecution;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerConfigInterface;

/**
 * Class TaskRunnerConfig.
 *
 * Infrastructure configuration wrapper built on top of generic Configuration storage.
 *
 * @package Logeecom\Infrastructure\TaskExecution
 */
class TaskRunnerConfig implements TaskRunnerConfigInterface
{
    const DEFAULT_MAX_STARTED_TASK_LIMIT = 8;
    const DEFAULT_QUEUE_NAME = 'default';

    /**
     * Threshold between two runs of scheduler.
     */
    const SCHEDULER_TIME_THRESHOLD = 60;
    const DEFAULT_ASYNC_CALL_METHOD = 'POST';
    const DEFAULT_ASYNC_STARTER_BATCH_SIZE = 8;

    /**
     * Max inactivity period for a task in seconds
     */
    const MAX_TASK_INACTIVITY_PERIOD = 60;

    /**
     * Default scheduler queue name.
     */
    const DEFAULT_SCHEDULER_QUEUE_NAME = 'SchedulerCheckTaskQueue';

    /**
     * Default task retention time expressed in days. After this time tasks are not necesseary any more in the system.
     */
    const DEFAULT_MAX_TASK_AGE = 7;

    /**
     * @var Configuration
     */
    private $config;

    /**
     * @var AsyncProcessUrlProviderInterface
     */
    private $asyncUrlProvider;

    /**
     * @param Configuration                 $config
     * @param AsyncProcessUrlProviderInterface $asyncUrlProvider
     */
    public function __construct(Configuration $config, AsyncProcessUrlProviderInterface $asyncUrlProvider)
    {
        $this->config = $config;
        $this->asyncUrlProvider = $asyncUrlProvider;
    }

    /**
     * Returns async process starter URL (infra concern).
     *
     * @param string $guid
     *
     * @return string
     */
    public function getAsyncProcessUrl($guid)
    {
        return $this->asyncUrlProvider->getAsyncProcessUrl($guid);
    }

    /**
     * @return string
     */
    public function getAutoConfigurationUrl()
    {
        return $this->getAsyncProcessUrl('auto-configure');
    }

    /**
     * @return string
     */
    public function getAsyncProcessCallHttpMethod()
    {
        return $this->getValue('asyncProcessCallHttpMethod', self::DEFAULT_ASYNC_CALL_METHOD);
    }

    /**
     * Retrieves max task age in days. Tasks older than the given number of days are no longer needed in the system.
     *
     * @return int Max task age in days.
     */
    public function getMaxTaskAge()
    {
        return $this->getValue('maxTaskAge', self::DEFAULT_MAX_TASK_AGE);
    }

    /**
     * @param string $method
     */
    public function setAsyncProcessCallHttpMethod($method)
    {
        $this->setValue('asyncProcessCallHttpMethod', $method);
    }

    /**
     * @return int
     */
    public function getAsyncStarterBatchSize()
    {
        return (int)$this->getValue('asyncStarterBatchSize', self::DEFAULT_ASYNC_STARTER_BATCH_SIZE);
    }

    /**delete
     * Sets max task age.
     *
     * @param int $maxAge Positive integer. Denotes max task age in days.
     */
    public function setMaxTaskAge($maxAge)
    {
        $this->setValue('maxTaskAge', $maxAge);
    }
    /**
     * @param int $size
     */
    public function setAsyncStarterBatchSize($size)
    {
        $this->setValue('asyncStarterBatchSize', (int)$size);
    }

    /**
     * @return string
     */
    public function getDefaultQueueName()
    {
        return $this->getValue('defaultQueueName', self::DEFAULT_QUEUE_NAME);
    }

    /**
     * @return int
     */
    public function getMaxStartedTasksLimit()
    {
        return (int)$this->getValue('maxStartedTasksLimit', self::DEFAULT_MAX_STARTED_TASK_LIMIT);
    }

    /**
     * @param int $limit
     */
    public function setMaxStartedTasksLimit($limit)
    {
        $this->setValue('maxStartedTasksLimit', (int)$limit);
    }

    /**
     * @return int|null
     */
    public function getTaskRunnerWakeupDelay()
    {
        $value = $this->getValue('taskRunnerWakeupDelay', null);

        return $value === null ? null : (int)$value;
    }

    /**
     * @param int $delay
     */
    public function setTaskRunnerWakeupDelay($delay)
    {
        $this->setValue('taskRunnerWakeupDelay', (int)$delay);
    }

    /**
     * @return int|null
     */
    public function getTaskRunnerMaxAliveTime()
    {
        $value = $this->getValue('taskRunnerMaxAliveTime', null);

        return $value === null ? null : (int)$value;
    }

    /**
     * @param int $delay
     */
    public function setTaskRunnerMaxAliveTime($delay)
    {
        $this->setValue('taskRunnerMaxAliveTime', (int)$delay);
    }

    /**
     * @return int|null
     */
    public function getMaxTaskExecutionRetries()
    {
        $value = $this->getValue('maxTaskExecutionRetries', null);

        return $value === null ? null : (int)$value;
    }

    /**
     * @param int $retries
     */
    public function setMaxTaskExecutionRetries($retries)
    {
        $this->setValue('maxTaskExecutionRetries', (int)$retries);
    }

    /**
     * @return int|null
     */
    public function getMaxTaskInactivityPeriod()
    {
         return $this->getValue('maxTaskInactivityPeriod', self::MAX_TASK_INACTIVITY_PERIOD);
    }

    /**
     * @param int $period
     */
    public function setMaxTaskInactivityPeriod($period)
    {
        $this->setValue('maxTaskInactivityPeriod', (int)$period);
    }

    /**
     * Returns task runner status information.
     *
     * @return array
     */
    public function getTaskRunnerStatus()
    {
        $value = $this->getValue('taskRunnerStatus', array());

        return is_array($value) ? $value : array();
    }

    /**
     * @param string $guid
     * @param int    $timestamp
     *
     * @throws TaskRunnerStatusStorageUnavailableException
     */
    public function setTaskRunnerStatus($guid, $timestamp)
    {
        $payload = array('guid' => $guid, 'timestamp' => (int)$timestamp);
        $configEntity = $this->setValue('taskRunnerStatus', $payload);

        if (!$configEntity || !$configEntity->getId()) {
            throw new TaskRunnerStatusStorageUnavailableException('Task runner status storage is not available.');
        }
    }

    /**
     * @return int|null
     */
    public function getSyncRequestTimeout()
    {
        $value = $this->getValue('syncRequestTimeout', null);

        return $value === null ? null : (int)$value;
    }

    /**
     * @param int $timeout
     */
    public function setSyncRequestTimeout($timeout)
    {
        $this->setValue('syncRequestTimeout', (int)$timeout);
    }

    /**
     * @return int|null
     */
    public function getAsyncRequestTimeout()
    {
        $value = $this->getValue('asyncRequestTimeout', null);

        return $value === null ? null : (int)$value;
    }

    /**
     * @param int $timeout
     */
    public function setAsyncRequestTimeout($timeout)
    {
        $this->setValue('asyncRequestTimeout', (int)$timeout);
    }

    /**
     * @return bool
     */
    public function isAsyncRequestWithProgress()
    {
        return (bool)$this->getValue('asyncRequestWithProgress', false);
    }

    /**
     * @param bool $withProgress
     */
    public function setAsyncRequestWithProgress($withProgress)
    {
        $this->setValue('asyncRequestWithProgress', (bool)$withProgress);
    }


    /**
     * Returns scheduler time threshold between checks.
     *
     * @return int Threshold in seconds.
     */
    public function getSchedulerTimeThreshold()
    {
        return $this->getValue('schedulerTimeThreshold', static::SCHEDULER_TIME_THRESHOLD);
    }

    /**
     * Sets scheduler time threshold between checks.
     *
     * @param int $schedulerTimeThreshold Threshold in seconds.
     */
    public function setSchedulerTimeThreshold($schedulerTimeThreshold)
    {
        $this->setValue('schedulerTimeThreshold', $schedulerTimeThreshold);
    }


    /**
     * Returns scheduler queue name.
     *
     * @return string Queue name.
     */
    public function getSchedulerQueueName()
    {
        return $this->getValue('schedulerQueueName', static::DEFAULT_SCHEDULER_QUEUE_NAME);
    }


    /**
     * Reads a value from the underlying configuration storage.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    private function getValue($key, $default = null)
    {
        // Requires Configuration to expose public get()/set() wrappers.
        return $this->config->get($key, $default);
    }

    /**
     * Persists a value to the underlying configuration storage.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return mixed
     */
    private function setValue($key, $value)
    {
        return $this->config->set($key, $value);
    }
}