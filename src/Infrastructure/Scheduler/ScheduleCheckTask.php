<?php

namespace Logeecom\Infrastructure\Scheduler;

use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\Serializer\Serializer;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskExecutorInterface;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerConfigInterface;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskStatusProviderInterface;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Infrastructure\TaskExecution\Task;
use Logeecom\Infrastructure\Utility\TimeProvider;
use Logeecom\Infrastructure\Scheduler\Models\Schedule;
use Packlink\BusinessLogic\Tasks\LegacyTaskAdapter;
use Packlink\BusinessLogic\Tasks\TaskExecutionConfig;
use Logeecom\Infrastructure\TaskExecution\Model\TaskStatus as CoreTaskStatus;

/**
 * Class ScheduleCheckTask.
 *
 * @package Logeecom\Infrastructure\Scheduler
 */
class ScheduleCheckTask extends Task
{
    /**
     * @var \Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface
     */
    private $repository;

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
        return new static();
    }

    /**
     * Transforms serializable object into an array.
     *
     * @return array Array representation of a serializable object.
     */
    public function toArray()
    {
        return array();
    }

    /**
     * @inheritDoc
     */
    public function __serialize()
    {
        return $this->toArray();
    }

    /**
     * @inheritDoc
     */
    public function __unserialize($data)
    {
    }

    /**
     * Runs task logic.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function execute()
    {
        /** @var Schedule $schedule */
        foreach ($this->getSchedules() as $schedule) {
            try {
                $this->enqueueScheduledTask($schedule);
            } catch (QueueStorageUnavailableException $ex) {
                Logger::logDebug(
                    'Failed to enqueue task ' . ($schedule->getTask() ? $schedule->getTask()->getType() : ''),
                    'Core',
                    array(
                        'ExceptionMessage' => $ex->getMessage(),
                        'ExceptionTrace' => $ex->getTraceAsString(),
                        'TaskData' => Serializer::serialize($schedule->getTask()),
                    )
                );
            }
        }

        $this->reportProgress(100);
    }

    /**
     * Enqueues scheduled task.
     *
     * @param $schedule
     *
     * @throws QueueStorageUnavailableException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function enqueueScheduledTask($schedule)
    {
        $task = $schedule->getTask();
        if (!$task) {
            return;
        }

        /** @var TaskStatusProviderInterface $statusProvider */
        $statusProvider = ServiceRegister::getService(TaskStatusProviderInterface::CLASS_NAME);

        /** @var CoreTaskStatus $latestStatus */
        $latestStatus = $statusProvider->getLatestStatus($task->getType(), $schedule->getContext());


        // If schedule is recurring, do not enqueue if task is already waiting or running
        // (canonical statuses, independent of backend)
        if ($schedule->isRecurring() && $this->isAlreadyScheduledOrRunning($latestStatus)) {
            return;
        }

        /** @var TaskExecutorInterface $taskExecutor */
        $taskExecutor = ServiceRegister::getService(TaskExecutorInterface::CLASS_NAME);

        /** @var Configuration $configuration */
        $configuration = ServiceRegister::getService(Configuration::CLASS_NAME);

        $queueName = $schedule->getQueueName() ?: $this->taskRunnerConfig()->getDefaultQueueName();
        $context = $schedule->getContext() ?: $configuration->getContext();

        $taskExecutor->enqueue(
            new LegacyTaskAdapter(
                $task,
                new TaskExecutionConfig($queueName, $task->getPriority(), $context)
            )
        );
        $this->updateSchedule($schedule);
    }

    /**
     * Checks if schedule is recurring.
     * If it is - updates next schedule time,
     * if it isn't - deletes schedule.
     *
     * @param $schedule
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function updateSchedule($schedule)
    {
        if ($schedule->isRecurring()) {
            $schedule->setNextSchedule();
            $this->getRepository()->update($schedule);
        } else {
            $this->getRepository()->delete($schedule);
        }
    }

    /**
     * Returns current date and time
     *
     * @return \DateTime
     */
    protected function now()
    {
        /** @var TimeProvider $timeProvider */
        $timeProvider = ServiceRegister::getService(TimeProvider::CLASS_NAME);

        return $timeProvider->getCurrentLocalTime();
    }

    /**
     * Determines whether a recurring task should be considered already scheduled for execution.
     *
     * We prevent duplicate enqueue if the latest status indicates the task is waiting/running.
     * This logic is backend-agnostic (works for Queue, Action Scheduler, cron, etc.).
     *
     * @param CoreTaskStatus $latestStatus
     *
     * @return bool
     */
    private function isAlreadyScheduledOrRunning(CoreTaskStatus $latestStatus): bool
    {
        return in_array(
            $latestStatus->getStatus(),
            array(
                CoreTaskStatus::PENDING,
                CoreTaskStatus::RUNNING,
            ),
            true
        );
    }

    /** @noinspection PhpDocMissingThrowsInspection */
    /**
     * Returns an array of Schedules that are due for execution
     *
     * @return \Logeecom\Infrastructure\ORM\Entity[]
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    private function getSchedules()
    {
        $queryFilter = new QueryFilter();
        /** @noinspection PhpUnhandledExceptionInspection */
        $queryFilter->where('nextSchedule', '<=', $this->now());

        return $this->getRepository()->select($queryFilter);
    }

    /**
     * Returns repository instance
     *
     * @return \Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    private function getRepository()
    {
        if ($this->repository === null) {
            /** @var \Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface $repository */
            $this->repository = RepositoryRegistry::getRepository(Schedule::CLASS_NAME);
        }

        return $this->repository;
    }

    /**
     * Returns task runner config instance
     *
     * @return TaskRunnerConfigInterface
     */
    private function taskRunnerConfig()
    {
        /**@var \Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerConfigInterface $taskRunnerConfig */
        $taskRunnerConfig = ServiceRegister::getService(TaskRunnerConfigInterface::CLASS_NAME);

        return $taskRunnerConfig;
    }
}
