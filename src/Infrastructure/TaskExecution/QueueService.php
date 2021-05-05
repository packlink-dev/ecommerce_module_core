<?php

namespace Logeecom\Infrastructure\TaskExecution;

use BadMethodCallException;
use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\ORM\Interfaces\QueueItemRepository;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Events\BeforeQueueStatusChangeEvent;
use Logeecom\Infrastructure\TaskExecution\Events\QueueStatusChangedEvent;
use Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemSaveException;
use Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use Logeecom\Infrastructure\TaskExecution\Interfaces\Priority;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup;
use Logeecom\Infrastructure\Utility\Events\EventBus;
use Logeecom\Infrastructure\Utility\TimeProvider;

/**
 * Class Queue.
 *
 * @package Logeecom\Infrastructure\TaskExecution
 */
class QueueService
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Maximum failure retries count
     */
    const MAX_RETRIES = 5;
    /**
     * A storage for task queue.
     *
     * @var RepositoryRegistry
     */
    private $storage;
    /**
     * Time provider instance.
     *
     * @var TimeProvider
     */
    private $timeProvider;
    /**
     * Task runner wakeup instance.
     *
     * @var TaskRunnerWakeup
     */
    private $taskRunnerWakeup;
    /**
     * Configuration service instance.
     *
     * @var Configuration
     */
    private $configService;

    /**
     * Enqueues queue item to a given queue and stores changes.
     *
     * @param string $queueName Name of a queue where queue item should be queued.
     * @param Task $task Task to enqueue.
     * @param string $context Task execution context. If integration supports multiple accounts (middleware
     *     integration) context based on account id should be provided. Failing to do this will result in global task
     *     context and unpredictable task execution.
     *
     * @param int | null $priority Null priority falls back to Priority::NORMAL
     *
     * @return \Logeecom\Infrastructure\TaskExecution\QueueItem Created queue item.
     *
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException When queue storage
     *      fails to save the item.
     */
    public function enqueue($queueName, Task $task, $context = '', $priority = null)
    {
        $queueItem = new QueueItem($task);
        $queueItem->setStatus(QueueItem::QUEUED);
        $queueItem->setQueueName($queueName);
        $queueItem->setContext($context);
        $queueItem->setQueueTimestamp($this->getTimeProvider()->getCurrentLocalTime()->getTimestamp());
        $queueItem->setPriority($priority ?: ($task->getPriority() ?: Priority::NORMAL));

        $this->save($queueItem, array(), true, QueueItem::CREATED);

        $this->getTaskRunnerWakeup()->wakeup();

        return $queueItem;
    }

    /**
     * Starts task execution, puts queue item in "in_progress" state and stores queue item changes.
     *
     * @param QueueItem $queueItem Queue item to start.
     *
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\AbortTaskExecutionException
     */
    public function start(QueueItem $queueItem)
    {
        if ($queueItem->getStatus() !== QueueItem::QUEUED) {
            $this->throwIllegalTransitionException($queueItem->getStatus(), QueueItem::IN_PROGRESS);
        }

        $lastUpdateTimestamp = $queueItem->getLastUpdateTimestamp();

        $queueItem->setStatus(QueueItem::IN_PROGRESS);
        $queueItem->setStartTimestamp($this->getTimeProvider()->getCurrentLocalTime()->getTimestamp());
        $queueItem->setLastUpdateTimestamp($queueItem->getStartTimestamp());

        $this->save(
            $queueItem,
            array('status' => QueueItem::QUEUED, 'lastUpdateTimestamp' => $lastUpdateTimestamp),
            true,
            QueueItem::QUEUED
        );

        $queueItem->getTask()->execute();
    }

    /**
     * Puts queue item in finished status and stores changes.
     *
     * @param QueueItem $queueItem Queue item to finish.
     *
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function finish(QueueItem $queueItem)
    {
        if ($queueItem->getStatus() !== QueueItem::IN_PROGRESS) {
            $this->throwIllegalTransitionException($queueItem->getStatus(), QueueItem::COMPLETED);
        }

        $queueItem->setStatus(QueueItem::COMPLETED);
        $queueItem->setFinishTimestamp($this->getTimeProvider()->getCurrentLocalTime()->getTimestamp());
        $queueItem->setProgressBasePoints(10000);

        $this->save(
            $queueItem,
            array('status' => QueueItem::IN_PROGRESS, 'lastUpdateTimestamp' => $queueItem->getLastUpdateTimestamp()),
            true,
            QueueItem::IN_PROGRESS
        );
    }

    /**
     * Returns queue item back to queue and sets updates last execution progress to current progress value.
     *
     * @param QueueItem $queueItem Queue item to requeue.
     *
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function requeue(QueueItem $queueItem)
    {
        if ($queueItem->getStatus() !== QueueItem::IN_PROGRESS) {
            $this->throwIllegalTransitionException($queueItem->getStatus(), QueueItem::QUEUED);
        }

        $lastExecutionProgress = $queueItem->getLastExecutionProgressBasePoints();

        $queueItem->setStatus(QueueItem::QUEUED);
        $queueItem->setStartTimestamp(null);
        $queueItem->setLastExecutionProgressBasePoints($queueItem->getProgressBasePoints());

        $this->save(
            $queueItem,
            array(
                'status' => QueueItem::IN_PROGRESS,
                'lastExecutionProgress' => $lastExecutionProgress,
                'lastUpdateTimestamp' => $queueItem->getLastUpdateTimestamp(),
            ),
            true,
            QueueItem::IN_PROGRESS
        );
    }

    /**
     * Returns queue item back to queue and increments retries count.
     * When max retries count is reached puts item in failed status.
     *
     * @param QueueItem $queueItem Queue item to fail.
     * @param string $failureDescription Verbal description of failure.
     *
     * @throws \BadMethodCallException Queue item must be in "in_progress" status for fail method.
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function fail(QueueItem $queueItem, $failureDescription)
    {
        if ($queueItem->getStatus() !== QueueItem::IN_PROGRESS) {
            $this->throwIllegalTransitionException($queueItem->getStatus(), QueueItem::FAILED);
        }

        $queueItem->setRetries($queueItem->getRetries() + 1);
        $queueItem->setFailureDescription(
            ($queueItem->getFailureDescription() ? ($queueItem->getFailureDescription() . "\n") : '')
            . 'Attempt ' . $queueItem->getRetries() . ': ' . $failureDescription
        );

        if ($queueItem->getRetries() > $this->getMaxRetries()) {
            $queueItem->setStatus(QueueItem::FAILED);
            $queueItem->setFailTimestamp($this->getTimeProvider()->getCurrentLocalTime()->getTimestamp());
        } else {
            $queueItem->setStatus(QueueItem::QUEUED);
            $queueItem->setStartTimestamp(null);
        }

        $this->save(
            $queueItem,
            array(
                'status' => QueueItem::IN_PROGRESS,
                'lastExecutionProgress' => $queueItem->getLastExecutionProgressBasePoints(),
                'lastUpdateTimestamp' => $queueItem->getLastUpdateTimestamp(),
            ),
            true,
            QueueItem::IN_PROGRESS
        );
    }

    /**
     * Aborts the queue item. Aborted queue item will not be started again.
     *
     * @param QueueItem $queueItem Queue item to abort.
     * @param string $abortDescription Verbal description of the reason for abortion.
     *
     * @throws \BadMethodCallException Queue item must be in "in_progress" status for abort method.
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function abort(QueueItem $queueItem, $abortDescription)
    {
        if ($queueItem->getStatus() !== QueueItem::IN_PROGRESS) {
            $this->throwIllegalTransitionException($queueItem->getStatus(), QueueItem::ABORTED);
        }

        $queueItem->setStatus(QueueItem::ABORTED);
        $queueItem->setFailTimestamp($this->getTimeProvider()->getCurrentLocalTime()->getTimestamp());
        $queueItem->setFailureDescription(
            ($queueItem->getFailureDescription() ? ($queueItem->getFailureDescription() . "\n") : '')
            . 'Attempt ' . ($queueItem->getRetries() + 1) . ': ' . $abortDescription
        );

        $this->save(
            $queueItem,
            array(
                'status' => QueueItem::IN_PROGRESS,
                'lastExecutionProgress' => $queueItem->getLastExecutionProgressBasePoints(),
                'lastUpdateTimestamp' => $queueItem->getLastUpdateTimestamp(),
            ),
            true,
            QueueItem::IN_PROGRESS
        );
    }

    /**
     * Updates queue item progress.
     *
     * @param QueueItem $queueItem Queue item to be updated.
     * @param int $progress New progress.
     *
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function updateProgress(QueueItem $queueItem, $progress)
    {
        if ($queueItem->getStatus() !== QueueItem::IN_PROGRESS) {
            throw new BadMethodCallException('Progress reported for not started queue item.');
        }

        $lastExecutionProgress = $queueItem->getLastExecutionProgressBasePoints();
        $lastUpdateTimestamp = $queueItem->getLastUpdateTimestamp();

        $queueItem->setProgressBasePoints($progress);
        $queueItem->setLastUpdateTimestamp($this->getTimeProvider()->getCurrentLocalTime()->getTimestamp());

        $this->save(
            $queueItem,
            array(
                'status' => QueueItem::IN_PROGRESS,
                'lastExecutionProgress' => $lastExecutionProgress,
                'lastUpdateTimestamp' => $lastUpdateTimestamp,
            )
        );
    }

    /**
     * Keeps passed queue item alive by setting last update timestamp.
     *
     * @param QueueItem $queueItem Queue item to keep alive.
     *
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function keepAlive(QueueItem $queueItem)
    {
        $lastExecutionProgress = $queueItem->getLastExecutionProgressBasePoints();
        $lastUpdateTimestamp = $queueItem->getLastUpdateTimestamp();
        $queueItem->setLastUpdateTimestamp($this->getTimeProvider()->getCurrentLocalTime()->getTimestamp());

        $this->save(
            $queueItem,
            array(
                'status' => QueueItem::IN_PROGRESS,
                'lastExecutionProgress' => $lastExecutionProgress,
                'lastUpdateTimestamp' => $lastUpdateTimestamp,
            )
        );
    }

    /**
     * @noinspection PhpDocMissingThrowsInspection
     *
     * Finds queue item by Id.
     *
     * @param int $id Id of a queue item to find.
     *
     * @return QueueItem|null Queue item if found; otherwise, NULL.
     */
    public function find($id)
    {
        $filter = new QueryFilter();
        /** @noinspection PhpUnhandledExceptionInspection */
        $filter->where('id', '=', $id);

        return $this->getStorage()->selectOne($filter);
    }

    /**
     * @noinspection PhpDocMissingThrowsInspection
     *
     * Finds latest queue item by type.
     *
     * @param string $type Type of a queue item to find.
     * @param string $context Task scope restriction, default is global scope.
     *
     * @return QueueItem|null Queue item if found; otherwise, NULL.
     */
    public function findLatestByType($type, $context = '')
    {
        $filter = new QueryFilter();
        /** @noinspection PhpUnhandledExceptionInspection */
        $filter->where('taskType', '=', $type);
        if (!empty($context)) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $filter->where('context', '=', $context);
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        $filter->orderBy('queueTime', 'DESC');

        return $this->getStorage()->selectOne($filter);
    }

    /**
     * @noinspection PhpDocMissingThrowsInspection
     *
     * Finds queue items with status "in_progress".
     *
     * @return QueueItem[] Running queue items.
     */
    public function findRunningItems()
    {
        $filter = new QueryFilter();
        /** @noinspection PhpUnhandledExceptionInspection */
        $filter->where('status', '=', QueueItem::IN_PROGRESS);

        return $this->getStorage()->select($filter);
    }

    /**
     * Finds list of earliest queued queue items per queue.
     * Only queues that doesn't have running tasks are taken in consideration.
     * Returned queue items are ordered in the descending priority.
     *
     * @param int $limit Result set limit. By default max 10 earliest queue items will be returned.
     *
     * @return QueueItem[] An array of found queue items.
     */
    public function findOldestQueuedItems($limit = 10)
    {
        $result = array();
        $currentLimit = $limit;

        foreach (QueueItem::getAvailablePriorities() as $priority) {
            $batch = $this->getStorage()->findOldestQueuedItems($priority, $currentLimit);
            $result[] = $batch;

            if (($currentLimit -= count($batch)) <= 0) {
                break;
            }
        }

        $result = !empty($result) ? call_user_func_array('array_merge', $result) : $result;

        return array_slice($result, 0, $limit);
    }

    /**
     * Creates or updates given queue item using storage service. If queue item id is not set, new queue item will be
     * created; otherwise, update will be performed.
     *
     * @param QueueItem $queueItem Item to save.
     * @param array $additionalWhere List of key/value pairs to set in where clause when saving queue item.
     * @param bool $reportStateChange Indicates whether to invoke a status change event.
     * @param string $previousState If event should be invoked, indicates the previous state.
     *
     * @return int Id of saved queue item.
     *
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    protected function save(
        QueueItem $queueItem,
        array $additionalWhere = array(),
        $reportStateChange = false,
        $previousState = ''
    ) {
        try {
            if ($reportStateChange) {
                $this->reportBeforeStatusChange($queueItem, $previousState);
            }

            $id = $this->getStorage()->saveWithCondition($queueItem, $additionalWhere);
            $queueItem->setId($id);

            if ($reportStateChange) {
                $this->reportStatusChange($queueItem, $previousState);
            }

            return $id;
        } catch (QueueItemSaveException $exception) {
            throw new QueueStorageUnavailableException('Unable to update the task.', $exception);
        }
    }

    /**
     * Fires event for before status change.
     *
     * @param \Logeecom\Infrastructure\TaskExecution\QueueItem $queueItem Queue item with is about to change status.
     * @param string $previousState Previous state. MUST be one of the states defined as constants in @see QueueItem.
     */
    private function reportBeforeStatusChange(QueueItem $queueItem, $previousState)
    {
        /** @var EventBus $eventBus */
        $eventBus = ServiceRegister::getService(EventBus::CLASS_NAME);
        $eventBus->fire(new BeforeQueueStatusChangeEvent($queueItem, $previousState));
    }

    /**
     * Fires event for status change.
     *
     * @param \Logeecom\Infrastructure\TaskExecution\QueueItem $queueItem Queue item with changed status.
     * @param string $previousState Previous state. MUST be one of the states defined as constants in @see QueueItem.
     */
    private function reportStatusChange(QueueItem $queueItem, $previousState)
    {
        /** @var EventBus $eventBus */
        $eventBus = ServiceRegister::getService(EventBus::CLASS_NAME);
        $eventBus->fire(new QueueStatusChangedEvent($queueItem, $previousState));
    }

    /**
     * @noinspection PhpDocMissingThrowsInspection
     *
     * Gets task storage instance.
     *
     * @return QueueItemRepository Task storage instance.
     */
    private function getStorage()
    {
        if ($this->storage === null) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $this->storage = RepositoryRegistry::getQueueItemRepository();
        }

        return $this->storage;
    }

    /**
     * Gets time provider instance.
     *
     * @return TimeProvider Time provider instance.
     */
    protected function getTimeProvider()
    {
        if ($this->timeProvider === null) {
            $this->timeProvider = ServiceRegister::getService(TimeProvider::CLASS_NAME);
        }

        return $this->timeProvider;
    }

    /**
     * Gets task runner wakeup instance.
     *
     * @return TaskRunnerWakeup Task runner wakeup instance.
     */
    protected function getTaskRunnerWakeup()
    {
        if ($this->taskRunnerWakeup === null) {
            $this->taskRunnerWakeup = ServiceRegister::getService(TaskRunnerWakeup::CLASS_NAME);
        }

        return $this->taskRunnerWakeup;
    }

    /**
     * Gets configuration service instance.
     *
     * @return Configuration Configuration service instance.
     */
    private function getConfigService()
    {
        if ($this->configService === null) {
            $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configService;
    }

    /**
     * Prepares exception message and throws exception.
     *
     * @param string $fromStatus A status form which status change is attempts.
     * @param string $toStatus A status to which status change is attempts.
     *
     * @throws \BadMethodCallException
     */
    private function throwIllegalTransitionException($fromStatus, $toStatus)
    {
        throw new BadMethodCallException(
            sprintf(
                'Illegal queue item state transition from "%s" to "%s"',
                $fromStatus,
                $toStatus
            )
        );
    }

    /**
     * Returns maximum number of retries.
     *
     * @return int Number of retries.
     */
    private function getMaxRetries()
    {
        $configurationValue = $this->getConfigService()->getMaxTaskExecutionRetries();

        return $configurationValue !== null ? $configurationValue : self::MAX_RETRIES;
    }
}
