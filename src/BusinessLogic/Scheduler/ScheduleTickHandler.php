<?php

namespace Packlink\BusinessLogic\Scheduler;

use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\Serializer\Serializer;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
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
     * Queues ScheduleCheckTask.
     */
    public function handle()
    {
        /** @var QueueService $queueService */
        $queueService = ServiceRegister::getService(QueueService::CLASS_NAME);
        /** @var Configuration $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        $task = $queueService->findLatestByType('ScheduleCheckTask');
        $threshold = $configService->getSchedulerTimeThreshold();

        if ($task === null || $task->getQueueTimestamp() + $threshold < time()) {
            $task = new ScheduleCheckTask();
            try {
                $queueService->enqueue($configService->getSchedulerQueueName(), $task);
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
    }
}
