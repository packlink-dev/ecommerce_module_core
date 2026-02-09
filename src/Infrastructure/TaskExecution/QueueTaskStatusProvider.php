<?php

namespace Logeecom\Infrastructure\TaskExecution;

use Logeecom\Infrastructure\TaskExecution\Interfaces\QueueServiceInterface;
use Logeecom\Infrastructure\TaskExecution\Model\TaskStatus;
use Logeecom\Infrastructure\Utility\TimeProvider;

class QueueTaskStatusProvider implements Interfaces\TaskStatusProviderInterface
{
    /**
     * @var QueueServiceInterface
     */
    private $queueService;

    /**
     * @var TimeProvider
     */
    private $timeProvider;


    public function __construct(QueueServiceInterface $queueService, TimeProvider $timeProvider)
    {
        $this->queueService = $queueService;
        $this->timeProvider = $timeProvider;
    }

    /**
     * Returns the latest execution status for the given task type and context.
     *
     * If no matching task is found, null is returned.
     *
     * @param string $type
     * @param string|null $context
     *
     * @return TaskStatus
     */
    public function getLatestStatus(string $type, $context = '')
    {
        $item = $this->queueService->findLatestByType($type, $context);

        if ($item === null) {
            return new TaskStatus(TaskStatus::NOT_FOUND);
        }

        return new TaskStatus(
            $this->mapQueueItemStatusToTaskStatus($item->getStatus()),
            $item->getFailureDescription()
        );
    }

    public function getLatestStatusWithExpiration(string $type, $context = '')
    {
        $item = $this->queueService->findLatestByType($type, $context);

        if ($item === null) {
            return new TaskStatus(TaskStatus::NOT_FOUND);
        }

        $queueStatus = $item->getStatus();

        if ($queueStatus === QueueItem::FAILED) {
            return new TaskStatus(TaskStatus::FAILED, $item->getFailureDescription());
        }

        if ($queueStatus === QueueItem::COMPLETED) {
            return new TaskStatus(TaskStatus::COMPLETED);
        }

        if ($this->isTaskExpired($item)) {
            return new TaskStatus(
                TaskStatus::EXPIRED,
                'Task expired due to inactivity.'
            );
        }

        return new TaskStatus(
            $this->mapQueueItemStatusToTaskStatus($queueStatus),
            $item->getFailureDescription()
        );
    }

    private function isTaskExpired(QueueItem $item): bool
    {
        $maxInactivity = $item->getTask()->getMaxInactivityPeriod();

        if ($maxInactivity === null) {
            return false;
        }

        $currentTimestamp = $this->timeProvider
            ->getCurrentLocalTime()
            ->getTimestamp();

        $taskTimestamp = $item->getLastUpdateTimestamp()
            ?: $item->getQueueTimestamp();

        return ($taskTimestamp + $maxInactivity) < $currentTimestamp;
    }

    private function mapQueueItemStatusToTaskStatus(string $queueItemStatus): string
    {
        switch ($queueItemStatus) {
            case QueueItem::CREATED:
                return TaskStatus::SCHEDULED;

            case QueueItem::QUEUED:
                return TaskStatus::PENDING;

            case QueueItem::IN_PROGRESS:
                return TaskStatus::RUNNING;

            case QueueItem::COMPLETED:
                return TaskStatus::COMPLETED;

            case QueueItem::FAILED:
                return TaskStatus::FAILED;

            case QueueItem::ABORTED:
                return TaskStatus::CANCELED;

            default:
                return TaskStatus::PENDING;
        }
    }
}