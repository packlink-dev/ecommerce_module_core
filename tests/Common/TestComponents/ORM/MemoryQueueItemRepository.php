<?php

namespace Logeecom\Tests\Common\TestComponents\ORM;

use Logeecom\Infrastructure\ORM\Entities\QueueItem;
use Logeecom\Infrastructure\ORM\Interfaces\QueueItemRepository;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\TaskExecution\QueueItem as QueueItemInterface;

class MemoryQueueItemRepository extends MemoryRepository implements QueueItemRepository
{
    /**
     * Fully qualified name of this class.
     */
    const THIS_CLASS_NAME = __CLASS__;

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
        $filter->where('status', '=', QueueItemInterface::IN_PROGRESS);

        $entities = $this->select($filter);
        $runningQueuesQuery = array();
        /** @var QueueItem $entity */
        foreach ($entities as $entity) {
            $runningQueuesQuery[] = $entity->queueName;
        }

        $filter = new QueryFilter();
        $filter->where('status', '=', QueueItemInterface::QUEUED);
        $filter->where('queueName', 'NOT IN', array_unique($runningQueuesQuery));
        $filter->orderBy('queueTimestamp', 'ASC');

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
            $queueName = $queueItem->queueName;
            if (!array_key_exists($queueName, $result)) {
                $result[$queueName] = $queueItem;
            }
        }

        $queueItems = array_values($result);
    }
}