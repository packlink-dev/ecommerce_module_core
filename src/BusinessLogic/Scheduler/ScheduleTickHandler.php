<?php

namespace Packlink\BusinessLogic\Scheduler;

use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\Serializer\Serializer;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Infrastructure\TaskExecution\QueueService;
use Packlink\BusinessLogic\Configuration;

/**
 * Class ScheduleTickHandler.
 *
 * @package Logeecom\Infrastructure\Scheduler
 */
class ScheduleTickHandler
{
    /**
     * @var QueueService
     */
    private $queueService;
    /**
     * @var Configuration
     */
    private $configService;

    /**
     * Queues ScheduleCheckTask.
     */
    public function handle()
    {
        $task = $this->getQueueService()->findLatestByType('ScheduleCheckTask');
        $threshold = $this->getConfigService()->getSchedulerTimeThreshold();

        $this->createCheckTask($task, $threshold);
    }

    /**
     * Checks if ScheduleCheckTask should be enqueued and
     * if it should, enqueues it.
     *
     * @param $task
     * @param $threshold
     */
    protected function createCheckTask($task, $threshold)
    {
        if ($task && in_array($task->getStatus(), array(QueueItem::QUEUED, QueueItem::IN_PROGRESS), true)) {
            return;
        }

        if ($task === null || $task->getQueueTimestamp() + $threshold < time()) {
            $this->enqueueCheckTask();
        }
    }

    /**
     * Enqueues ScheduleCheckTask.
     */
    protected function enqueueCheckTask()
    {
        $task = $this->getScheduleCheckTask();
        try {
            $this->getQueueService()->enqueue(
                $this->getConfigService()->getSchedulerQueueName(),
                $task,
                $this->getConfigService()->getContext(),
                $task->getPriority()
            );
        } catch (QueueStorageUnavailableException $ex) {
            Logger::logDebug(
                'Failed to enqueue task ' . $task->getType(),
                'Core',
                array(
                    'ExceptionMessage' => $ex->getMessage(),
                    'ExceptionTrace' => $ex->getTraceAsString(),
                    'TaskData' => Serializer::serialize($task),
                )
            );
        }
    }

    /**
     * Gets ScheduleCheckTask.
     *
     * @return ScheduleCheckTask
     */
    protected function getScheduleCheckTask()
    {
        return new ScheduleCheckTask();
    }

    /**
     * @return QueueService
     */
    protected function getQueueService()
    {
        if ($this->queueService === null) {
            $this->queueService = ServiceRegister::getService(QueueService::CLASS_NAME);
        }

        return $this->queueService;
    }

    /**
     * @return Configuration
     */
    protected function getConfigService()
    {
        if ($this->configService === null) {
            $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configService;
    }
}
