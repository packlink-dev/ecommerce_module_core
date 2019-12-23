<?php

namespace Packlink\BusinessLogic\DraftShipment;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Infrastructure\TaskExecution\QueueService;
use Packlink\BusinessLogic\BaseService;
use Packlink\BusinessLogic\DraftShipment\Objects\DraftShipmentStatus;
use Packlink\BusinessLogic\Scheduler\Models\HourlySchedule;
use Packlink\BusinessLogic\Scheduler\Models\Schedule;
use Packlink\BusinessLogic\Tasks\SendDraftTask;

/**
 * Class DraftShipmentService.
 *
 * @package Packlink\BusinessLogic\DraftShipment
 */
class DraftShipmentService extends BaseService
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;

    /**
     * Creates draft task for provided shop order id.
     *
     * @param string $orderId Shop order id.
     *
     * @param bool $isDelayed Indicates if the creation of the task should be delayed.
     */
    public function createDraftShipmentTask($orderId, $isDelayed = false)
    {
        /** @var OrderSendDraftTaskMapService $draftTaskMapService */
        $draftTaskMapService = ServiceRegister::getService(OrderSendDraftTaskMapService::CLASS_NAME);
        if ($draftTaskMapService->getOrderTaskMap($orderId) !== null) {
            return;
        }

        $draftTaskMapService->createOrderTaskMap($orderId);

        /** @var QueueService $queue */
        $queue = ServiceRegister::getService(QueueService::CLASS_NAME);
        /** @var Configuration $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);

        $sendDraftTask = new SendDraftTask($orderId);
        if (!$isDelayed) {
            $queue->enqueue($configService->getDefaultQueueName(), $sendDraftTask, $configService->getContext());

            if ($sendDraftTask->getExecutionId() !== null) {
                $draftTaskMapService->setExecutionId($orderId, $sendDraftTask->getExecutionId());
            }
        } else {
            $this->enqueueDelayedSendDraftTask($sendDraftTask, $configService);
        }
    }

    /**
     * Returns the status of the CreateDraftTask.
     *
     * @param string $orderId The Order ID.
     *
     * @return DraftShipmentStatus Entity with correct status and optional failure message.
     */
    public function getDraftStatus($orderId)
    {
        /** @var OrderSendDraftTaskMapService $taskMapService */
        $taskMapService = ServiceRegister::getService(OrderSendDraftTaskMapService::CLASS_NAME);
        $draftShipmentStatus = new DraftShipmentStatus();
        $taskMap = $taskMapService->getOrderTaskMap($orderId);

        if ($taskMap === null) {
            $draftShipmentStatus->status = DraftShipmentStatus::NOT_QUEUED;
        } elseif ($taskMap->getExecutionId() === null) {
            $draftShipmentStatus->status = DraftShipmentStatus::DELAYED;
        } else {
            /** @var QueueService $queue */
            $queue = ServiceRegister::getService(QueueService::CLASS_NAME);
            $task = $queue->find($taskMap->getExecutionId());
            if ($task !== null) {
                $draftShipmentStatus->status = $task->getStatus();
                $draftShipmentStatus->message = $task->getFailureDescription();
            } else {
                $draftShipmentStatus->status = QueueItem::FAILED;
            }
        }

        return $draftShipmentStatus;
    }

    /**
     * Enqueues delayed send draft task.
     *
     * @param SendDraftTask $task Task to be executed.
     * @param Configuration $configService Configuration service.
     */
    protected function enqueueDelayedSendDraftTask(SendDraftTask $task, Configuration $configService)
    {
        $timestamp = strtotime('+5 minutes');

        $schedule = new HourlySchedule($task, $configService->getDefaultQueueName(), $configService->getContext());
        $schedule->setMonth((int)date('m', $timestamp));
        $schedule->setDay((int)date('d', $timestamp));
        $schedule->setHour((int)date('H', $timestamp));
        $schedule->setMinute((int)date('i', $timestamp));
        $schedule->setRecurring(false);
        $schedule->setNextSchedule();

        RepositoryRegistry::getRepository(Schedule::CLASS_NAME)->save($schedule);
    }
}
