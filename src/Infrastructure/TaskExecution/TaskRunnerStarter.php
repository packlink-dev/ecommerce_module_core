<?php

namespace Logeecom\Infrastructure\TaskExecution;

use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\Serializer\Serializer;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerRunException;
use Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException;
use Logeecom\Infrastructure\TaskExecution\Interfaces\Runnable;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerStatusStorage;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup;
use Logeecom\Infrastructure\TaskExecution\TaskEvents\TickEvent;
use Logeecom\Infrastructure\Utility\Events\EventBus;

/**
 * Class TaskRunnerStarter.
 *
 * @package Logeecom\Infrastructure\TaskExecution
 */
class TaskRunnerStarter implements Runnable
{
    /**
     * Unique runner guid.
     *
     * @var string
     */
    private $guid;
    /**
     * Instance of task runner status storage.
     *
     * @var TaskRunnerStatusStorage
     */
    private $runnerStatusStorage;
    /**
     * Instance of task runner.
     *
     * @var TaskRunner
     */
    private $taskRunner;
    /**
     * Instance of task runner wakeup service.
     *
     * @var TaskRunnerWakeup
     */
    private $taskWakeup;

    /**
     * TaskRunnerStarter constructor.
     *
     * @param string $guid Unique runner guid.
     */
    public function __construct($guid)
    {
        $this->guid = $guid;
    }

    /**
     * Transforms array into an serializable object,
     *
     * @param array $array Data that is used to instantiate serializable object.
     *
     * @return \Logeecom\Infrastructure\Serializer\Interfaces\Serializable
     *      Instance of serialized object.
     */
    public static function fromArray(array $array)
    {
        return new static($array['guid']);
    }

    /**
     * Transforms serializable object into an array.
     *
     * @return array Array representation of a serializable object.
     */
    public function toArray()
    {
        return array('guid' => $this->guid);
    }

    /**
     * String representation of object.
     *
     * @inheritdoc
     */
    public function serialize()
    {
        return Serializer::serialize(array($this->guid));
    }

    /**
     * Constructs the object.
     *
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        list($this->guid) = Serializer::unserialize($serialized);
    }

    /**
     * Get unique runner guid.
     *
     * @return string Unique runner string.
     */
    public function getGuid()
    {
        return $this->guid;
    }

    /**
     * Starts synchronously currently active task runner instance.
     */
    public function run()
    {
        try {
            $this->doRun();
        } catch (TaskRunnerStatusStorageUnavailableException $ex) {
            Logger::logError(
                'Failed to run task runner. Runner status storage unavailable.',
                'Core',
                array('ExceptionMessage' => $ex->getMessage())
            );
            Logger::logDebug(
                'Failed to run task runner. Runner status storage unavailable.',
                'Core',
                array(
                    'ExceptionMessage' => $ex->getMessage(),
                    'ExceptionTrace' => $ex->getTraceAsString(),
                )
            );
        } catch (TaskRunnerRunException $ex) {
            Logger::logInfo($ex->getMessage());
            Logger::logDebug($ex->getMessage(), 'Core', array('ExceptionTrace' => $ex->getTraceAsString()));
        } catch (\Exception $ex) {
            Logger::logError(
                'Failed to run task runner. Unexpected error occurred.',
                'Core',
                array('ExceptionMessage' => $ex->getMessage())
            );
            Logger::logDebug(
                'Failed to run task runner. Unexpected error occurred.',
                'Core',
                array(
                    'ExceptionMessage' => $ex->getMessage(),
                    'ExceptionTrace' => $ex->getTraceAsString(),
                )
            );
        }
    }

    /**
     * Runs task execution.
     *
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerRunException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException
     */
    private function doRun()
    {
        $runnerStatus = $this->getRunnerStorage()->getStatus();
        if ($this->guid !== $runnerStatus->getGuid()) {
            throw new TaskRunnerRunException('Failed to run task runner. Runner guid is not set as active.');
        }

        if ($runnerStatus->isExpired()) {
            $this->getTaskWakeup()->wakeup();
            throw new TaskRunnerRunException('Failed to run task runner. Runner is expired.');
        }

        $this->getTaskRunner()->setGuid($this->guid);
        $this->getTaskRunner()->run();

        /** @var EventBus $eventBus */
        $eventBus = ServiceRegister::getService(EventBus::CLASS_NAME);
        $eventBus->fire(new TickEvent());
    }

    /**
     * Gets task runner status storage instance.
     *
     * @return TaskRunnerStatusStorage Instance of runner status storage service.
     */
    private function getRunnerStorage()
    {
        if ($this->runnerStatusStorage === null) {
            $this->runnerStatusStorage = ServiceRegister::getService(TaskRunnerStatusStorage::CLASS_NAME);
        }

        return $this->runnerStatusStorage;
    }

    /**
     * Gets task runner instance.
     *
     * @return TaskRunner Instance of runner service.
     */
    private function getTaskRunner()
    {
        if ($this->taskRunner === null) {
            $this->taskRunner = ServiceRegister::getService(TaskRunner::CLASS_NAME);
        }

        return $this->taskRunner;
    }

    /**
     * Gets task runner wakeup instance.
     *
     * @return TaskRunnerWakeup Instance of runner wakeup service.
     */
    private function getTaskWakeup()
    {
        if ($this->taskWakeup === null) {
            $this->taskWakeup = ServiceRegister::getService(TaskRunnerWakeup::CLASS_NAME);
        }

        return $this->taskWakeup;
    }
}
