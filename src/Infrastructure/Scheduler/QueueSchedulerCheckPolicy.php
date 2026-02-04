<?php

namespace Logeecom\Infrastructure\Scheduler;

use Logeecom\Infrastructure\Scheduler\Interfaces\SchedulerCheckPolicyInterface;
use Logeecom\Infrastructure\TaskExecution\Interfaces\QueueServiceInterface;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerConfigInterface;
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


    /**
     * @var TaskRunnerConfigInterface $taskRunnerConfig
     */
    private $taskRunnerConfig;
    public function __construct(QueueServiceInterface $queueService, Configuration $configService,
                                TaskRunnerConfigInterface $taskRunnerConfig)
    {
        $this->queueService = $queueService;
        $this->configService = $configService;
        $this->taskRunnerConfig = $taskRunnerConfig;
    }

    public function shouldEnqueue()
    {
        $task = $this->queueService->findLatestByType('ScheduleCheckTask');
        $threshold = $this->taskRunnerConfig->getSchedulerTimeThreshold();

        if ($task === null) {
            return true;
        }

        if (in_array($task->getStatus(), array(QueueItem::QUEUED, QueueItem::IN_PROGRESS), true)) {
            return $task->getQueueTimestamp() + $threshold < time();
        }

        return $task->getQueueTimestamp() + $threshold < time();
    }
}
