<?php

namespace Logeecom\Infrastructure\TaskExecution;

use Logeecom\Infrastructure\TaskExecution\Interfaces\QueueServiceInterface;
use Logeecom\Infrastructure\TaskExecution\Model\TaskStatus;

class QueueTaskStatusProvider implements Interfaces\TaskStatusProviderInterface
{
    /**
     * @var QueueServiceInterface
     */
    private $queueService;


    public function __construct(QueueServiceInterface $queueService)
    {
        $this->queueService = $queueService;
    }

    /**
     * Returns the latest execution status for the given task type and context.
     *
     * If no matching task is found, null is returned.
     *
     * @param string $type
     * @param string $context
     *
     * @return TaskStatus|null
     */
    public function getLatestStatus(string $type, string $context = '')
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