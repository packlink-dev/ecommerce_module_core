<?php

namespace Logeecom\Tests\Infrastructure\Common\TestComponents\ORM;

use Logeecom\Infrastructure\ORM\Interfaces\QueueItemRepository;
use Logeecom\Infrastructure\ORM\QueryFilter\Operators;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemSaveException;
use Logeecom\Infrastructure\TaskExecution\QueueItem;

class MemoryQueueItemRepository extends MemoryRepository implements QueueItemRepository
{
    /**
     * Fully qualified name of this class.
     */
    const THIS_CLASS_NAME = __CLASS__;
    /**
     * Disabled flag.
     *
     * @var bool
     */
    public $disabled = false;

    /**
     * Finds list of earliest queued queue items per queue. Following list of criteria for searching must be satisfied:
     *      - Queue must be without already running queue items
     *      - For one queue only one (oldest queued) item should be returned
     *
     * @param int $limit Result set limit. By default max 10 earliest queue items will be returned
     *
     * @return QueueItem[] Found queue item list
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\EntityClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function findOldestQueuedItems($limit = 10)
    {
        $filter = new QueryFilter();
        $filter->where('status', '=', QueueItem::IN_PROGRESS);

        $entities = $this->select($filter);
        $runningQueuesQuery = array();
        /** @var QueueItem $entity */
        foreach ($entities as $entity) {
            $runningQueuesQuery[] = $entity->getQueueName();
        }

        $filter = new QueryFilter();
        $filter->where('status', '=', QueueItem::QUEUED);
        $filter->where('queueName', 'NOT IN', array_unique($runningQueuesQuery));
        $filter->orderBy('queueTime', 'ASC');

        $results = $this->select($filter);
        $this->groupByQueueName($results);

        return array_slice($results, 0, $limit);
    }

    /**
     * @param QueueItem[] $queueItems
     */
    private function groupByQueueName(array &$queueItems)
    {
        $result = array();
        foreach ($queueItems as $queueItem) {
            $queueName = $queueItem->getQueueName();
            if (!array_key_exists($queueName, $result)) {
                $result[$queueName] = $queueItem;
            }
        }

        $queueItems = array_values($result);
    }

    /**
     * Creates or updates given queue item. If queue item id is not set, new queue item will be created otherwise
     * update will be performed.
     *
     * @param QueueItem $queueItem Item to save
     * @param array $additionalWhere List of key/value pairs that must be satisfied upon saving queue item. Key is
     *                               queue item property and value is condition value for that property. Example for
     *     MySql storage:
     *                               $storage->save($queueItem, array('status' => 'queued')) should produce query
     *                               UPDATE queue_storage_table SET .... WHERE .... AND status => 'queued'
     *
     * @return int Id of saved queue item
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\EntityClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemSaveException
     */
    public function saveWithCondition(QueueItem $queueItem, array $additionalWhere = array())
    {
        if ($this->disabled) {
            throw new QueueItemSaveException('Failed to save queue item due to save restriction rule.');
        }

        if ($queueItem->getId()) {
            $this->updateQueueItem($queueItem, $additionalWhere);

            return $queueItem->getId();
        }

        return $this->save($queueItem);
    }

    /**
     * Updates queue item.
     *
     * @param \Logeecom\Infrastructure\TaskExecution\QueueItem $queueItem
     * @param array $additionalWhere
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\EntityClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemSaveException
     */
    protected function updateQueueItem(QueueItem $queueItem, array $additionalWhere)
    {
        $filter = new QueryFilter();
        $filter->where('id', Operators::EQUALS, $queueItem->getId());

        foreach ($additionalWhere as $name => $value) {
            if ($value === null) {
                $filter->where($name, Operators::NULL);
            } else {
                $filter->where($name, Operators::EQUALS, $value);
            }
        }

        /** @var QueueItem $item */
        $item = $this->selectOne($filter);
        if ($item === null) {
            throw new QueueItemSaveException("Cannot update queue item with id {$queueItem->getId()}.");
        }

        $this->update($queueItem);
    }
}