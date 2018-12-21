<?php

namespace Logeecom\Tests\Common\TestComponents\TaskExecution;

use Logeecom\Infrastructure\Interfaces\Required\TaskQueueStorage;
use Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemSaveException;
use Logeecom\Infrastructure\TaskExecution\QueueItem;

class InMemoryTestQueueStorage implements TaskQueueStorage
{

    /** @var int Autoincrement id */
    private $autoId = 1;

    private $disabled = false;

    private $queue = array();

    public function getQueue()
    {
        return $this->queue;
    }

    public function getQueueItem($id)
    {
        return array_key_exists($id, $this->queue) ? $this->queue[$id] : null;
    }

    public function disable()
    {
        $this->disabled = true;
    }

    /**
     * Creates or updates given queue item. If queue item id is not set, new queue item will be created otherwise
     * update will be performed.
     *
     * @param QueueItem $queueItem Item to save
     * @param array $additionalWhere List of key/value pairs to set in where clause when saving queue item. Key is
     *     queue item field and value is condition value for that field. Example for MySql storage:
     *      $storage->save($queueItem, array('status' => 'queued')) should produce query
     *      UPDATE queue_storage_table SET .... WHERE .... AND status => 'queued'
     *
     * @return int Id of saved queue item
     * @throws QueueItemSaveException if queue item could not be saved
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    public function save(QueueItem $queueItem, array $additionalWhere = array())
    {
        if ($this->disabled) {
            throw new QueueItemSaveException('Failed to save queue item due to save restriction rule.');
        }

        $id = $queueItem->getId();
        if (empty($id)) {
            $id = $this->autoId++;
        } else if (!empty($additionalWhere)) {
            foreach ($additionalWhere as $field => $value) {
                if (array_key_exists($field, $this->queue[$id]) && $this->queue[$id][$field] !== $value) {
                    throw new QueueItemSaveException('Failed to save queue item due to save restriction rule.');
                }
            }
        }

        $this->queue[$id] = array(
            'id' => $id,
            'status' => $queueItem->getStatus(),
            'type' => $queueItem->getTaskType(),
            'queueName' => $queueItem->getQueueName(),
            'context' => $queueItem->getContext(),
            'lastExecutionProgress' => $queueItem->getLastExecutionProgressBasePoints(),
            'progress' => $queueItem->getProgressBasePoints(),
            'retries' => $queueItem->getRetries(),
            'failureDescription' => $queueItem->getFailureDescription(),
            'serializedTask' => $queueItem->getSerializedTask(),
            'createTimestamp' => $queueItem->getCreateTimestamp(),
            'queueTimestamp' => $queueItem->getQueueTimestamp(),
            'lastUpdateTimestamp' => $queueItem->getLastUpdateTimestamp(),
            'startTimestamp' => $queueItem->getStartTimestamp(),
            'finishTimestamp' => $queueItem->getFinishTimestamp(),
            'failTimestamp' => $queueItem->getFinishTimestamp(),
            'earliestStartTimestamp' => $queueItem->getEarliestStartTimestamp(),
        );

        return $id;
    }

    /**
     * Finds queue item by id
     *
     * @param int $id Id of a queue item to find
     *
     * @return QueueItem|null Found queue item or null when queue item does not exist
     */
    public function find($id)
    {
        if ($this->disabled || !array_key_exists($id, $this->queue)) {
            return null;
        }

        return $this->restoreQueueItem($id);
    }

    /**
     * Finds list of earliest queued queue items per queue. Only queues that doesn't have running tasks are taken in consideration.
     *
     * @param int $limit Result set limit. By default max 10 earliest queue items will be returned
     *
     * @return QueueItem[] Found queue item list
     */
    public function findOldestQueuedItems($limit = 10)
    {
        $result = array();
        $runningQueues = $this->findRunningQueues();
        foreach ($this->getSortedQueue(array('queueTimestamp' => TaskQueueStorage::SORT_ASC)) as $item) {
            if (
                empty($item['queueTimestamp']) ||
                QueueItem::QUEUED !== $item['status'] ||
                in_array($item['queueName'], $runningQueues, false)
            ) {
                continue;
            }

            /** @var QueueItem $queueItemInResult */
            $queueItemInResult = !empty($result[$item['queueName']]) ? $result[$item['queueName']] : null;

            if ($queueItemInResult === null || $item['queueTimestamp'] < $queueItemInResult->getQueueTimestamp()) {
                $result[$item['queueName']] = $this->restoreQueueItem($item['id']);
            }

            if (count($result) === $limit) {
                break;
            }
        }

        return array_values($result);
    }

    private function findRunningQueues()
    {
        $runningQueueItems = $this->findAll(array('status' => QueueItem::IN_PROGRESS));
        return array_map(function (QueueItem $runningQueueItem) {
            return $runningQueueItem->getQueueName();
        }, $runningQueueItems);
    }

    /**
     * @inheritdoc
     */
    public function findAll(array $filterBy = array(), array $sortBy = array(), $start = 0, $limit = 10)
    {
        if ($this->disabled) {
            return array();
        }

        $result = array();
        $startCounter = 0;
        $limitCounter = 0;

        $queue = $this->getSortedQueue($sortBy);
        foreach ($queue as $item) {
            if ($this->doesItemSatisfyFilter($item, $filterBy)) {
                $startCounter++;
                if ($startCounter > $start) {
                    $result[] = $this->restoreQueueItem($item['id']);
                    $limitCounter++;
                }
            }

            if ($limitCounter === $limit) {
                break;
            }
        }

        return $result;
    }

    private function doesItemSatisfyFilter(array $item, array $filterBy) {
        foreach ($filterBy as $filterField => $filterValue) {
            if (empty($item[$filterField]) || ($item[$filterField] !== $filterValue)) {
                return false;
            }
        }

        return true;
    }

    private function getSortedQueue(array $sortBy)
    {
        $queue = $this->queue;

        if (empty($sortBy)) {
            return $queue;
        }

        $sortKey = key($sortBy);
        $sortValue = $sortBy[$sortKey];
        usort($queue, function ($item1, $item2) use ($sortKey, $sortValue) {
            if ($item1[$sortKey] === $item2[$sortKey]) {
                return 0;
            }

            if ('ASC' === $sortValue) {
                return ($item1[$sortKey] < $item2[$sortKey]) ? -1 : 1;
            }

            return ($item1[$sortKey] < $item2[$sortKey]) ? 1 : -1;
        });

        return $queue;
    }

    /**
     * @param $id
     *
     * @return QueueItem
     */
    private function restoreQueueItem($id)
    {
        $queueItem = new QueueItem();
        $queueItem->setId($id);
        $queueItem->setStatus($this->queue[$id]['status']);
        $queueItem->setQueueName($this->queue[$id]['queueName']);
        $queueItem->setContext($this->queue[$id]['context']);
        $queueItem->setLastExecutionProgressBasePoints($this->queue[$id]['lastExecutionProgress']);
        $queueItem->setProgressBasePoints($this->queue[$id]['progress']);
        $queueItem->setRetries($this->queue[$id]['retries']);
        $queueItem->setFailureDescription($this->queue[$id]['failureDescription']);
        $queueItem->setSerializedTask($this->queue[$id]['serializedTask']);
        $queueItem->setCreateTimestamp($this->queue[$id]['createTimestamp']);
        $queueItem->setQueueTimestamp($this->queue[$id]['queueTimestamp']);
        $queueItem->setLastUpdateTimestamp($this->queue[$id]['lastUpdateTimestamp']);
        $queueItem->setStartTimestamp($this->queue[$id]['startTimestamp']);
        $queueItem->setFinishTimestamp($this->queue[$id]['finishTimestamp']);
        $queueItem->setFailTimestamp($this->queue[$id]['failTimestamp']);
        $queueItem->setEarliestStartTimestamp($this->queue[$id]['earliestStartTimestamp']);

        return $queueItem;
    }

    /**
     * Finds latest queue item by type
     *
     * @param string $type Type of a queue item to find
     *
     * @param string $context Task scope restriction if provided search will be limited to given task scope. Leave empty for global
     * scope search (across all task scopes)
     *
     * @return QueueItem|null Found queue item or null when queue item does not exist
     */
    public function findLatestByType($type, $context = '')
    {
        $filterBy = array('type' => $type);
        if (!empty($context)) {
            $filterBy['context'] = $context;
        }

        $items = $this->findAll($filterBy, array('queueTimestamp' => TaskQueueStorage::SORT_DESC), 0, 1);

        return !empty($items) ? $items[0] : null;
    }
}