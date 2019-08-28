<?php

namespace Packlink\BusinessLogic\Controllers;

use Logeecom\Infrastructure\ORM\QueryFilter\Operators;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Infrastructure\Utility\TimeProvider;

/**
 * Class UpdateShippingServicesTaskStatusController.
 *
 * @package Packlink\BusinessLogic\Controllers
 */
class UpdateShippingServicesTaskStatusController
{
    /**
     * Checks the status of the task responsible for getting services.
     *
     * @return string <p>One of the following statuses:
     *  QueueItem::FAILED - when the task failed or the task does not exist,
     *  QueueItem::COMPLETED - when the task completed successfully,
     *  QueueItem::IN_PROGRESS - when the task is in progress.
     * </p>
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    public function getLastTaskStatus()
    {
        $repo = RepositoryRegistry::getQueueItemRepository();
        $filter = new QueryFilter();
        $filter->where('taskType', Operators::EQUALS, 'UpdateShippingServicesTask');
        $filter->orderBy('queueTime', 'DESC');

        $item = $repo->selectOne($filter);
        if ($item) {
            $status = $item->getStatus();
            if ($status === QueueItem::FAILED || $status === QueueItem::COMPLETED) {
                return $status;
            }

            /** @var TimeProvider $timeProvider */
            $timeProvider = ServiceRegister::getService(TimeProvider::CLASS_NAME);
            $currentTimestamp = $timeProvider->getCurrentLocalTime()->getTimestamp();
            $taskTimestamp = $item->getLastUpdateTimestamp() ?: $item->getQueueTimestamp();
            $expired = $taskTimestamp + $item->getTask()->getMaxInactivityPeriod() < $currentTimestamp;

            return $expired ? QueueItem::FAILED : $status;
        }

        return QueueItem::FAILED;
    }
}
