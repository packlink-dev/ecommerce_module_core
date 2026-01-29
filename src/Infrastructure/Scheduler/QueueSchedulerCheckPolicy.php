<?php

namespace Logeecom\Infrastructure\Scheduler;

use Logeecom\Infrastructure\Scheduler\Interfaces\SchedulerCheckPolicyInterface;
use Logeecom\Infrastructure\TaskExecution\Interfaces\QueueServiceInterface;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Packlink\BusinessLogic\Configuration;

class QueueSchedulerCheckPolicy implements SchedulerCheckPolicyInterface
{
    /**
     * @var QueueServiceInterface
     */
    private $queueService;
    /**
     * @var Configuration
     */
    private $configService;

    public function __construct(QueueServiceInterface $queueService, Configuration $configService)
    {
        $this->queueService = $queueService;
        $this->configService = $configService;
    }

    public function shouldEnqueue()
    {
        $task = $this->queueService->findLatestByType('ScheduleCheckTask');
        $threshold = $this->configService->getSchedulerTimeThreshold();

        if ($task === null) {
            return true;
        }

        if (in_array($task->getStatus(), array(QueueItem::QUEUED, QueueItem::IN_PROGRESS), true)) {
            return $task->getQueueTimestamp() + $threshold < time();
        }

        return $task->getQueueTimestamp() + $threshold < time();
    }
}
