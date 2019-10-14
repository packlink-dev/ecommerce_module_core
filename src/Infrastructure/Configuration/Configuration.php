<?php
/** @noinspection PhpDocMissingThrowsInspection */

/** @noinspection PhpUnusedParameterInspection */

namespace Logeecom\Infrastructure\Configuration;

use Logeecom\Infrastructure\Http\DTO\OptionsDTO;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\Singleton;
use Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException;

/**
 * Class Configuration.
 *
 * @package Logeecom\Infrastructure\Configuration
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
     * Default HTTP method to use for async call.
     */
    const ASYNC_CALL_METHOD = 'POST';
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
     * Returns async process starter url, always in http.
     *
     * @param string $guid Process identifier.
     *
     * @return string Formatted URL of async process starter endpoint.
     */
    abstract public function getAsyncProcessUrl($guid);

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
        return $this->getConfigValue('defaultLoggerEnabled', false);
    }

    /**
     * Sets debug mode status (enabled/disabled).
     *
     * @param bool $status TRUE if debug mode is enabled; otherwise, false.
     */
    public function setDebugModeEnabled($status)
    {
        $this->saveConfigValue('debugModeEnabled', (bool)$status);
    }

    /**
     * Returns debug mode status.
     *
     * @return bool TRUE if debug mode is enabled; otherwise, false.
     */
    public function isDebugModeEnabled()
    {
        return $this->getConfigValue('debugModeEnabled', false);
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
     * Gets current auto-configuration state.
     *
     * @return string Current state.
     */
    public function getAutoConfigurationState()
    {
        return $this->getConfigValue('autoConfigurationState', '');
    }

    /**
     * Gets auto-configuration controller URL.
     *
     * @return string Auto-configuration URL.
     */
    public function getAutoConfigurationUrl()
    {
        return $this->getAsyncProcessUrl('auto-configure');
    }

    /**
     * Sets current auto-configuration state.
     *
     * @param string $state Current state.
     */
    public function setAutoConfigurationState($state)
    {
        $this->saveConfigValue('autoConfigurationState', $state);
    }

    /**
     * Gets current HTTP configuration options for given domain.
     *
     * @param string $domain A domain for which to return configuration options.
     *
     * @return \Logeecom\Infrastructure\Http\DTO\OptionsDTO[]
     */
    public function getHttpConfigurationOptions($domain)
    {
        $data = json_decode($this->getConfigValue('httpConfigurationOptions', '[]'), true);
        if (isset($data[$domain])) {
            return OptionsDTO::fromArrayBatch($data[$domain]);
        }

        return array();
    }

    /**
     * Sets HTTP configuration options for given domain.
     *
     * @param string $domain A domain for which to save configuration options.
     *
     * @param OptionsDTO[] $options HTTP configuration options
     */
    public function setHttpConfigurationOptions($domain, array $options)
    {
        // get all current options and append new ones for given domain
        $data = json_decode($this->getConfigValue('httpConfigurationOptions', '[]'), true);
        $data[$domain] = array();
        foreach ($options as $option) {
            $data[$domain][] = $option->toArray();
        }

        $this->saveConfigValue('httpConfigurationOptions', json_encode($data));
    }

    /**
     * Sets the auto-test mode flag.
     *
     * @param bool $status
     */
    public function setAutoTestMode($status)
    {
        $this->saveConfigValue('autoTestMode', $status);
    }

    /**
     * Returns whether the auto-test mode is active.
     *
     * @return bool TRUE if the auto-test mode is active; otherwise, FALSE.
     */
    public function isAutoTestMode()
    {
        return (bool)$this->getConfigValue('autoTestMode', false);
    }

    /**
     * Sets the HTTP method to be used for the async call.
     *
     * @param string $method Http method (GET or POST).
     */
    public function setAsyncProcessCallHttpMethod($method)
    {
        $this->saveConfigValue('asyncProcessCallHttpMethod', $method);
    }

    /**
     * Returns current HTTP method used for the async call.
     *
     * @return string The async call HTTP method (GET or POST).
     */
    public function getAsyncProcessCallHttpMethod()
    {
        return $this->getConfigValue('asyncProcessCallHttpMethod', static::ASYNC_CALL_METHOD);
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

        return $entity ? $entity->getValue() : $default;
    }

    /**
     * Returns configuration entity with provided name.
     *
     * @param string $name Configuration property name.
     *
     * @return \Logeecom\Infrastructure\Configuration\ConfigEntity Configuration entity, if found; otherwise, null;
     */
    protected function getConfigEntity($name)
    {
        $filter = new QueryFilter();
        /** @noinspection PhpUnhandledExceptionInspection */
        $filter->where('name', '=', $name);
        if ($this->isSystemSpecific($name)) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $filter->where('systemId', '=', $this->getCurrentSystemId());
        }

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
     * @return \Logeecom\Infrastructure\Configuration\ConfigEntity
     */
    protected function saveConfigValue($name, $value)
    {
        /** @var ConfigEntity $config */
        $config = $this->getConfigEntity($name) ?: new ConfigEntity();
        if ($this->isSystemSpecific($name)) {
            $config->setSystemId($this->getCurrentSystemId());
        }

        $config->setValue($value);
        if ($config->getId() === null) {
            $config->setName($name);
            $this->getRepository()->save($config);
        } else {
            $this->getRepository()->update($config);
        }

        return $config;
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
        return true;
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
