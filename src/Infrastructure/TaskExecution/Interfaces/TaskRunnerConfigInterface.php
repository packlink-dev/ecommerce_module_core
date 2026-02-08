<?php

namespace Logeecom\Infrastructure\TaskExecution\Interfaces;

use Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException;

/**
 * Interface TaskRunnerConfigInterface.
 *
 * Defines infrastructure configuration semantics for background execution (TaskRunner/Async starter).
 */
interface TaskRunnerConfigInterface
{
    const CLASS_NAME = __CLASS__;

    /**
     * Returns async process starter URL.
     *
     * @param string $guid
     *
     * @return string
     */
    public function getAsyncProcessUrl($guid);

    /**
     * Returns auto-configuration URL.
     *
     * @return string
     */
    public function getAutoConfigurationUrl();

    /**
     * Returns HTTP method used for async process call.
     *
     * @return string
     */
    public function getAsyncProcessCallHttpMethod();

    /**
     * Sets HTTP method used for async process call.
     *
     * @param string $method
     */
    public function setAsyncProcessCallHttpMethod($method);

    /**
     * Sets max task age.
     *
     * @param int $maxAge Positive integer. Denotes max task age in days.
     */
    public function setMaxTaskAge($maxAge);

    /**
     * Returns async starter batch size.
     *
     * @return int
     */
    public function getAsyncStarterBatchSize();

    /**
     * Sets async starter batch size.
     *
     * @param int $size
     */
    public function setAsyncStarterBatchSize($size);

    /**
     * Returns default queue name.
     *
     * @return string
     */
    public function getDefaultQueueName();

    /**
     * Returns max started tasks limit.
     *
     * @return int
     */
    public function getMaxStartedTasksLimit();

    /**
     * Sets max started tasks limit.
     *
     * @param int $limit
     */
    public function setMaxStartedTasksLimit($limit);

    /**
     * Returns task runner wakeup delay in seconds.
     *
     * @return int|null
     */
    public function getTaskRunnerWakeupDelay();

    /**delete
     * Returns scheduler time threshold between checks.
     *
     * @return int Threshold in seconds.
     */
    public function getSchedulerTimeThreshold();

    /**
     * Sets task runner wakeup delay in seconds.
     *
     * @param int $delay
     */
    public function setTaskRunnerWakeupDelay($delay);

    /**
     * Returns task runner max alive time in seconds.
     *
     * @return int|null
     */
    public function getTaskRunnerMaxAliveTime();

    /**
     * Sets task runner max alive time in seconds.
     *
     * @param int $delay
     */
    public function setTaskRunnerMaxAliveTime($delay);

    /**
     * Returns maximum number of task execution retries.
     *
     * @return int|null
     */
    public function getMaxTaskExecutionRetries();

    /**
     * Sets maximum number of task execution retries.
     *
     * @param int $retries
     */
    public function setMaxTaskExecutionRetries($retries);

    /**
     * Returns max task inactivity period in seconds.
     *
     * @return int|null
     */
    public function getMaxTaskInactivityPeriod();

    /**
     * Sets max task inactivity period in seconds.
     *
     * @param int $period
     */
    public function setMaxTaskInactivityPeriod($period);

    /**
     * Returns task runner status information.
     *
     * Expected keys: guid, timestamp.
     *
     * @return array
     */
    public function getTaskRunnerStatus();

    /**
     * Sets task runner status information.
     *
     * @param string $guid
     * @param int    $timestamp
     *
     * @throws TaskRunnerStatusStorageUnavailableException
     */
    public function setTaskRunnerStatus($guid, $timestamp);

    /**
     * Returns sync request timeout in milliseconds.
     *
     * @return int|null
     */
    public function getSyncRequestTimeout();

    /**
     * Sets sync request timeout in milliseconds.
     *
     * @param int $timeout
     */
    public function setSyncRequestTimeout($timeout);

    /**
     * Returns async request timeout in milliseconds.
     *
     * @return int|null
     */
    public function getAsyncRequestTimeout();

    /**
     * Sets async request timeout in milliseconds.
     *
     * @param int $timeout
     */
    public function setAsyncRequestTimeout($timeout);

    /**
     * Returns whether async requests should be aborted using progress callback.
     *
     * @return bool
     */
    public function isAsyncRequestWithProgress();

    /**
     * Sets flag for using progress callback abort mechanism.
     *
     * @param bool $withProgress
     */
    public function setAsyncRequestWithProgress($withProgress);

    /**
     * Retrieves max task age in days.
     *
     * @return int
     */
    public function getMaxTaskAge();

    /**
     * Sets scheduler time threshold between checks.
     *
     * @param int $schedulerTimeThreshold
     */
    public function setSchedulerTimeThreshold($schedulerTimeThreshold);

    /**
     * Returns scheduler queue name.
     *
     * @return string
     */
    public function getSchedulerQueueName();
}