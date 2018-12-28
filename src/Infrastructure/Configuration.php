<?php

namespace Logeecom\Infrastructure;

use Logeecom\Infrastructure\ORM\Entities\ConfigEntity;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException;

/**
 * Interface Configuration.
 *
 * @package Logeecom\Infrastructure\Interfaces\Required
 */
abstract class Configuration extends Singleton
{
    /**
     * Fully qualified name of this interface.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Minimal log level
     */
    const MIN_LOG_LEVEL = 3;
    /**
     * Default maximum number of tasks that can run in the same time
     */
    const DEFAULT_MAX_STARTED_TASK_LIMIT = 8;
    /**
     * Default queue name.
     */
    const DEFAULT_QUEUE_NAME = 'default';
    /**
     * System user context.
     *
     * @var string
     */
    protected $context;
    /**
     * Configuration repository.
     *
     * @var \Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface
     */
    protected $repository;

    /**
     * Retrieves integration name.
     *
     * @return string Integration name.
     */
    abstract public function getIntegrationName();

    /**
     * Returns current system identifier.
     *
     * @return string Current system identifier.
     */
    abstract public function getCurrentSystemId();

    /**
     * Resets authorization credentials to null
     */
    abstract public function resetAuthorizationCredentials();

    /**
     * Sets task execution context.
     *
     * When integration supports multiple accounts (middleware integration) proper context must be set based on
     * middleware account that is using core library functionality. This context should then be used by business
     * services to fetch account specific data.Core will set context provided upon task enqueueing before task
     * execution.
     *
     * @param string $context Context to set.
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

    /**
     * Gets task execution context.
     *
     * @return string
     *  Context in which task is being executed. If no context is provided empty string is returned (global context).
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Saves min log level in integration database.
     *
     * @param int $minLogLevel Min log level.
     */
    public function saveMinLogLevel($minLogLevel)
    {
        $this->saveConfigValue('minLogLevel', $minLogLevel);
    }

    /**
     * Retrieves min log level from integration database.
     *
     * @return int Min log level.
     */
    public function getMinLogLevel()
    {
        return $this->getConfigValue('minLogLevel', static::MIN_LOG_LEVEL);
    }

    /**
     * Set default logger status (enabled/disabled).
     *
     * @param bool $status TRUE if default logger is enabled; otherwise, false.
     */
    public function setDefaultLoggerEnabled($status)
    {
        $this->saveConfigValue('defaultLoggerEnabled', $status);
    }

    /**
     * Return whether default logger is enabled or not.
     *
     * @return bool TRUE if default logger is enabled; otherwise, false.
     */
    public function isDefaultLoggerEnabled()
    {
        return $this->getConfigValue('defaultLoggerEnabled', true);
    }

    /**
     * Gets the number of maximum allowed started task at the point in time. This number will determine how many tasks
     * can be in "in_progress" status at the same time.
     *
     * @return int Max started tasks limit.
     */
    public function getMaxStartedTasksLimit()
    {
        return $this->getConfigValue('maxStartedTasksLimit', static::DEFAULT_MAX_STARTED_TASK_LIMIT);
    }

    /**
     * Sets the number of maximum allowed started task at the point in time. This number will determine how many tasks
     * can be in "in_progress" status at the same time.
     *
     * @param int $limit Max started tasks limit.
     */
    public function setMaxStartedTasksLimit($limit)
    {
        $this->saveConfigValue('maxStartedTasksLimit', $limit);
    }

    /**
     * Automatic task runner wakeup delay in seconds. Task runner will sleep at the end of its lifecycle for this value
     * seconds before it sends wakeup signal for a new lifecycle. Return null to use default system value (10).
     *
     * @return int|null Task runner wakeup delay in seconds if set; otherwise, null.
     */
    public function getTaskRunnerWakeupDelay()
    {
        return $this->getConfigValue('taskRunnerWakeupDelay');
    }

    /**
     * Gets maximal time in seconds allowed for runner instance to stay in alive (running) status. After this period
     * system will automatically start new runner instance and shutdown old one. Return null to use default system
     * value (60).
     *
     * @return int|null Task runner max alive time in seconds if set; otherwise, null;
     */
    public function getTaskRunnerMaxAliveTime()
    {
        return $this->getConfigValue('taskRunnerMaxAliveTime');
    }

    /**
     * Gets maximum number of failed task execution retries. System will retry task execution in case of error until
     * this number is reached. Return null to use default system value (5).
     *
     * @return int|null Number of max execution retries if set; otherwise, false.
     */
    public function getMaxTaskExecutionRetries()
    {
        return $this->getConfigValue('maxTaskExecutionRetries');
    }

    /**
     * Gets max inactivity period for a task in seconds. After inactivity period is passed, system will fail such tasks
     * as expired. Return null to use default system value (30).
     *
     * @return int|null Max task inactivity period in seconds if set; otherwise, null.
     */
    public function getMaxTaskInactivityPeriod()
    {
        return $this->getConfigValue('maxTaskInactivityPeriod');
    }

    /**
     * Returns task runner status information
     *
     * @return array Guid and timestamp information
     */
    public function getTaskRunnerStatus()
    {
        return $this->getConfigValue('taskRunnerStatus', array());
    }

    /**
     * Sets task runner status information as JSON encoded string.
     *
     * @param string $guid Global unique identifier.
     * @param int $timestamp Timestamp.
     *
     * @throws TaskRunnerStatusStorageUnavailableException
     */
    public function setTaskRunnerStatus($guid, $timestamp)
    {
        $taskRunnerStatus = array('guid' => $guid, 'timestamp' => $timestamp);
        $config = $this->saveConfigValue('taskRunnerStatus', $taskRunnerStatus);

        if (!$config || !$config->getId()) {
            throw new TaskRunnerStatusStorageUnavailableException('Task runner status storage is not available.');
        }
    }

    /**
     * Returns default queue name.
     *
     * @return string Default queue name.
     */
    public function getDefaultQueueName()
    {
        return $this->getConfigValue('defaultQueueName', static::DEFAULT_QUEUE_NAME);
    }

    /**
     * Gets configuration value for given name.
     *
     * @param string $name Name of the config parameter.
     * @param mixed $default Default value if config entity does not exist.
     *
     * @return mixed Value of config entity if found; otherwise, default value provided in $default parameter.
     */
    protected function getConfigValue($name, $default = null)
    {
        $entity = $this->getConfigEntity($name);

        return $entity ? $entity->value : $default;
    }

    /**
     * @noinspection PhpDocMissingThrowsInspection
     *
     * Returns configuration entity with provided name.
     *
     * @param string $name Configuration property name.
     *
     * @return \Logeecom\Infrastructure\ORM\Entities\ConfigEntity Configuration entity, if found; otherwise, null;
     */
    protected function getConfigEntity($name)
    {
        $filter = new QueryFilter();
        /** @noinspection PhpUnhandledExceptionInspection */
        $filter->where('name', '=', $name);
        /** @noinspection PhpUnhandledExceptionInspection */
        $filter->where('systemId', '=', $this->getCurrentSystemId());

        /** @var ConfigEntity $config */
        $config = $this->getRepository()->selectOne($filter);

        return $config;
    }

    /**
     * Saves configuration value or updates it if it already exists.
     *
     * @param string $name Configuration property name.
     * @param mixed $value Configuration property value.
     *
     * @return \Logeecom\Infrastructure\ORM\Entities\ConfigEntity
     */
    protected function saveConfigValue($name, $value)
    {
        /** @var ConfigEntity $config */
        $config = $this->getConfigEntity($name) ?: new ConfigEntity();
        $config->systemId = $this->getCurrentSystemId();
        $config->value = $value;
        if ($config->getId() === null) {
            $config->name = $name;
            $this->getRepository()->save($config);
        } else {
            $this->getRepository()->update($config);
        }

        return $config;
    }

    /** @noinspection PhpDocMissingThrowsInspection */
    /**
     * Returns repository instance.
     *
     * @return \Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface Configuration repository.
     */
    protected function getRepository()
    {
        if ($this->repository === null) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $this->repository = RepositoryRegistry::getRepository(ConfigEntity::getClassName());
        }

        return $this->repository;
    }
}
