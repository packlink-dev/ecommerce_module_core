<?php

namespace Packlink\BusinessLogic\Scheduler;

use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\Serializer\Serializer;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Infrastructure\TaskExecution\QueueService;
use Logeecom\Infrastructure\TaskExecution\Task;
use Logeecom\Infrastructure\Utility\TimeProvider;
use Packlink\BusinessLogic\Scheduler\Models\Schedule;

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
        /** @var QueueService $queueService */
        $queueService = ServiceRegister::getService(QueueService::CLASS_NAME);

        $task = $schedule->getTask();
        if (!$task) {
            return;
        }

        $latestTask = $queueService->findLatestByType($task->getType(), $schedule->getContext());
        if ($latestTask
            && $schedule->isRecurring()
            && in_array($latestTask->getStatus(), array(QueueItem::QUEUED, QueueItem::IN_PROGRESS), true)
        ) {
            // do not enqueue task if it is already scheduled for execution
            return;
        }

        $queueService->enqueue($schedule->getQueueName(), $task, $schedule->getContext(), $task->getPriority());
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
}
