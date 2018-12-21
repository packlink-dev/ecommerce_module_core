<?php

namespace Logeecom\Infrastructure\ORM\Interfaces;

use Logeecom\Infrastructure\ORM\Entities\QueueItem;

/**
 * Interface QueueRepository.
 *
 * @package Logeecom\Infrastructure\ORM\Interfaces
 */
interface QueueItemRepository extends RepositoryInterface
{
    /**
     * Finds list of earliest queued queue items per queue. Following list of criteria for searching must be satisfied:
     *      - Queue must be without already running queue items
     *      - For one queue only one (oldest queued) item should be returned
     *
     * @param int $limit Result set limit. By default max 10 earliest queue items will be returned
     *
     * @return QueueItem[] Found queue item list
     */
    public function findOldestQueuedItems($limit = 10);
}
