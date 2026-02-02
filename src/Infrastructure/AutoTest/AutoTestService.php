<?php

namespace Logeecom\Infrastructure\AutoTest;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\Exceptions\StorageNotAccessibleException;
use Logeecom\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use Logeecom\Infrastructure\Logger\LogData;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskExecutorInterface;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskStatusProviderInterface;
use Logeecom\Infrastructure\TaskExecution\Model\TaskStatus;
use Packlink\BusinessLogic\Tasks\BusinessTasks\AutoTestBusinessTask;

/**
 * Class AutoTestService.
 *
 * @package Logeecom\Infrastructure\AutoTest
 */
class AutoTestService
{
    const CLASS_NAME = __CLASS__;

    /**
     * Configuration service instance.
     *
     * @var Configuration
     */
    private $configService;
    /**
     * @var TaskExecutorInterface
     */
    private $taskExecutor;
    /**
     * @var TaskStatusProviderInterface
     */
    private $statusProvider;

    public function __construct(TaskExecutorInterface $taskExecutor, TaskStatusProviderInterface $statusProvider)
    {
        $this->taskExecutor = $taskExecutor;
        $this->statusProvider = $statusProvider;
    }

    /**
     * Starts the auto-test.
     *
     * @throws \Logeecom\Infrastructure\Exceptions\StorageNotAccessibleException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function startAutoTest()
    {
        try {
            $this->setAutoTestMode(true);
            $this->deletePreviousLogs();
            Logger::logInfo('Start auto-test');
        } catch (\Exception $e) {
            throw new StorageNotAccessibleException('Cannot start the auto-test because storage is not accessible.');
        }

        $this->logHttpOptions();

        $this->taskExecutor->enqueue(new AutoTestBusinessTask('DUMMY TEST DATA'));

        return (int)time();
    }

    /**
     * Activates the auto-test mode and registers the necessary components.
     *
     * @param bool $persist Indicates whether to store the mode change in configuration.
     */
    public function setAutoTestMode($persist = false)
    {
        Logger::resetInstance();
        ServiceRegister::registerService(
            ShopLoggerAdapter::CLASS_NAME,
            function () {
                return AutoTestLogger::getInstance();
            }
        );

        if ($persist) {
            $this->getConfigService()->setAutoTestMode(true);
        }
    }

    /**
     * Gets the status of the auto-test task.
     *
     * @param int $queueItemId The ID of the auto-test task (unused).
     *
     * @return AutoTestStatus The status of the auto-test task.
     */
    public function getAutoTestTaskStatus($queueItemId = 0)
    {
        $this->setAutoTestMode();

        $context = $this->getConfigService()->getContext();

        /** @var \Logeecom\Infrastructure\TaskExecution\Model\TaskStatus $result */
        $result = $this->statusProvider->getLatestStatus(
            'AutoTestBusinessTask',
            $context ? $context : ''
        );

        $status = $result->getStatus();
        $logs = AutoTestLogger::getInstance()->getLogs();

        if ($status === TaskStatus::PENDING && $this->isQueuedTimeout($logs)) {
            Logger::logError('Auto-test task did not finish within expected time frame.');
            $status = 'timeout';
        }
        $error = $status === 'timeout' ? 'Task could not be started.' : ($result->getMessage() ?? '');

        return new AutoTestStatus(
            $status,
            in_array($status, array('timeout', 'completed', 'failed'), true),
            $error,
            $logs
        );
    }

    /**
     * Resets the auto-test mode.
     * When auto-test finishes, this is needed to reset the flag in configuration service and
     * re-initialize shop logger. Otherwise, logs and async calls will still use auto-test mode.
     *
     * @param callable $loggerInitializerDelegate Delegate that will give instance of the shop logger service.
     */
    public function stopAutoTestMode($loggerInitializerDelegate)
    {
        $this->getConfigService()->setAutoTestMode(false);
        ServiceRegister::registerService(ShopLoggerAdapter::CLASS_NAME, $loggerInitializerDelegate);
        Logger::resetInstance();
    }

    /**
     * Deletes previous auto-test logs.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function deletePreviousLogs()
    {
        $repo = RepositoryRegistry::getRepository(LogData::getClassName());
        $logs = $repo->select();
        foreach ($logs as $log) {
            $repo->delete($log);
        }
    }

    /**
     * Logs current HTTP configuration options.
     */
    protected function logHttpOptions()
    {
        $testDomain = parse_url($this->getConfigService()->getAsyncProcessUrl(''), PHP_URL_HOST);
        $options = array();
        foreach ($this->getConfigService()->getHttpConfigurationOptions($testDomain) as $option) {
            $options[$option->getName()] = $option->getValue();
        }

        Logger::logInfo('HTTP configuration options', 'Core', array($testDomain => array('HTTPOptions' => $options)));
    }

    /**
     * Gets the configuration service instance.
     *
     * @return \Logeecom\Infrastructure\Configuration\Configuration Configuration service instance.
     */
    private function getConfigService()
    {
        if ($this->configService === null) {
            $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configService;
    }

    /**
     * Determines if queued status exceeded the timeout window.
     *
     * @param LogData[] $logs
     *
     * @return bool
     */
    private function isQueuedTimeout(array $logs)
    {
        $autoTestStart = $this->findLatestLogByMessage($logs, 'Start auto-test');
        if ($autoTestStart === null) {
            return false;
        }

        return $autoTestStart->getTimestamp() < time() - 30;
    }

    /**
     * Finds latest log by message.
     *
     * @param LogData[] $logs
     * @param string $message
     *
     * @return LogData|null
     */
    private function findLatestLogByMessage(array $logs, $message)
    {
        $latest = null;
        foreach ($logs as $log) {
            if ($log->getMessage() !== $message) {
                continue;
            }

            if ($latest === null || $log->getTimestamp() > $latest->getTimestamp()) {
                $latest = $log;
            }
        }

        return $latest;
    }
}
