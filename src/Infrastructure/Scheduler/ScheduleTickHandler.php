<?php

namespace Logeecom\Infrastructure\Scheduler;

use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\Serializer\Serializer;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskExecutorInterface;
use Logeecom\Infrastructure\Scheduler\Interfaces\SchedulerCheckPolicyInterface;
use Packlink\BusinessLogic\Tasks\LegacyTaskAdapter;

/**
 * Class ScheduleTickHandler.
 *
 * @package Logeecom\Infrastructure\Scheduler
 */
class ScheduleTickHandler
{
    /**
     * @var SchedulerCheckPolicyInterface
     */
    private $checkPolicy;
    /**
     * @var TaskExecutorInterface
     */
    private $taskExecutor;

    /**
     * Queues ScheduleCheckTask.
     */
    public function __construct(
        SchedulerCheckPolicyInterface $checkPolicy = null,
        TaskExecutorInterface $taskExecutor = null
    ) {
        $this->checkPolicy = $checkPolicy ?: ServiceRegister::getService(SchedulerCheckPolicyInterface::CLASS_NAME);
        $this->taskExecutor = $taskExecutor ?: ServiceRegister::getService(TaskExecutorInterface::CLASS_NAME);
    }

    public function handle()
    {
        if ($this->checkPolicy->shouldEnqueue()) {
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
            $this->taskExecutor->enqueue(new LegacyTaskAdapter($task));
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
}
