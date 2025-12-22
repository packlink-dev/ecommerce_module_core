<?php

namespace Logeecom\Infrastructure\TaskExecution\Interfaces;

use Logeecom\Infrastructure\TaskExecution\Exceptions\AbortTaskExecutionException;
use Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException;
use Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Infrastructure\TaskExecution\Task;

interface QueueServiceInterface
{
    /**
     * Enqueues queue item to a given queue and stores changes.
     *
     * @param string $queueName Name of a queue where queue item should be queued.
     * @param Task $task Task to enqueue.
     * @param string $context Task execution context.
     * @param int|null $priority Null priority falls back to Priority::NORMAL
     *
     * @return QueueItem Created queue item.
     *
     * @throws QueueStorageUnavailableException
     */
    public function enqueue($queueName, Task $task, $context = '', $priority = null);

    /**
     * Starts task execution, puts queue item in "in_progress" state and stores queue item changes.
     *
     * @param QueueItem $queueItem Queue item to start.
     *
     * @throws QueueItemDeserializationException
     * @throws QueueStorageUnavailableException
     * @throws AbortTaskExecutionException
     */
    public function start(QueueItem $queueItem);

    /**
     * Puts queue item in finished status and stores changes.
     *
     * @param QueueItem $queueItem Queue item to finish.
     *
     * @throws QueueStorageUnavailableException
     */
    public function finish(QueueItem $queueItem);

    /**
     * Returns queue item back to queue and sets updates last execution progress to current progress value.
     *
     * @param QueueItem $queueItem Queue item to requeue.
     *
     * @throws QueueStorageUnavailableException
     */
    public function requeue(QueueItem $queueItem);

    /**
     * Returns queue item back to queue and increments retries count.
     * When max retries count is reached puts item in failed status.
     *
     * @param QueueItem $queueItem Queue item to fail.
     * @param string $failureDescription Verbal description of failure.
     *
     * @throws \BadMethodCallException
     * @throws QueueStorageUnavailableException
     */
    public function fail(QueueItem $queueItem, $failureDescription);

    /**
     * Aborts the queue item. Aborted queue item will not be started again.
     *
     * @param QueueItem $queueItem Queue item to abort.
     * @param string $abortDescription Verbal description of the reason for abortion.
     *
     * @throws \BadMethodCallException
     * @throws QueueStorageUnavailableException
     */
    public function abort(QueueItem $queueItem, $abortDescription);

    /**
     * Updates queue item progress.
     *
     * @param QueueItem $queueItem Queue item to be updated.
     * @param int $progress New progress.
     *
     * @throws QueueStorageUnavailableException
     */
    public function updateProgress(QueueItem $queueItem, $progress);

    /**
     * Keeps passed queue item alive by setting last update timestamp.
     *
     * @param QueueItem $queueItem Queue item to keep alive.
     *
     * @throws QueueStorageUnavailableException
     */
    public function keepAlive(QueueItem $queueItem);

    /**
     * Finds queue item by Id.
     *
     * @param int $id Id of a queue item to find.
     *
     * @return QueueItem|null Queue item if found; otherwise, NULL.
     */
    public function find($id);

    /**
     * Finds latest queue item by type.
     *
     * @param string $type Type of a queue item to find.
     * @param string $context Task scope restriction, default is global scope.
     *
     * @return QueueItem|null Queue item if found; otherwise, NULL.
     */
    public function findLatestByType($type, $context = '');

    /**
     * Finds queue items with status "in_progress".
     *
     * @return QueueItem[] Running queue items.
     */
    public function findRunningItems();

    /**
     * Finds list of earliest queued queue items per queue.
     * Only queues that doesn't have running tasks are taken in consideration.
     * Returned queue items are ordered in the descending priority.
     *
     * @param int $limit Result set limit.
     *
     * @return QueueItem[] An array of found queue items.
     */
    public function findOldestQueuedItems($limit = 10);
}