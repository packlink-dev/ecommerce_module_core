<?php

namespace Packlink\BusinessLogic\ShipmentDraft;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Infrastructure\TaskExecution\QueueService;
use Logeecom\Infrastructure\Utility\TimeProvider;
use Packlink\BusinessLogic\BaseService;
use Packlink\BusinessLogic\Scheduler\Models\DailySchedule;
use Packlink\BusinessLogic\Scheduler\Models\HourlySchedule;
use Packlink\BusinessLogic\Scheduler\Models\Schedule;
use Packlink\BusinessLogic\ShipmentDraft\Objects\ShipmentDraftStatus;
use Packlink\BusinessLogic\ShippingMethod\Utility\ShipmentStatus;
use Packlink\BusinessLogic\Tasks\SendDraftTask;
use Packlink\BusinessLogic\Tasks\UpdateShipmentDataTask;

/**
 * Class ShipmentDraftService.
 *
 * @package Packlink\BusinessLogic\ShipmentDraft
 */
class ShipmentDraftService extends BaseService
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
     * Enqueues the task for creating shipment draft for provided order id.
     * Ensures proper mapping between the order and the created task are persisted.
     *
     * @param string $orderId Shop order id.
     * @param bool $isDelayed Indicates if the execution of the task should be delayed.
     * @param int $delayInterval Interval in minutes to delay the execution.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Packlink\BusinessLogic\ShipmentDraft\Exceptions\DraftTaskMapExists
     * @throws \Packlink\BusinessLogic\ShipmentDraft\Exceptions\DraftTaskMapNotFound
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function enqueueCreateShipmentDraftTask($orderId, $isDelayed = false, $delayInterval = 5)
    {
        /** @var OrderSendDraftTaskMapService $draftTaskMapService */
        $draftTaskMapService = ServiceRegister::getService(OrderSendDraftTaskMapService::CLASS_NAME);
        $orderTaskMap = $draftTaskMapService->getOrderTaskMap($orderId);
        if ($orderTaskMap !== null) {
            if (!$draftTaskMapService->isMappedTaskFailed($orderTaskMap)) {
                return;
            }
        } else {
            $draftTaskMapService->createOrderTaskMap($orderId);
        }

        /** @var \Packlink\BusinessLogic\Configuration $configService */
        $configService = $this->getConfigService();

        $sendDraftTask = new SendDraftTask($orderId);
        if (!$isDelayed) {
            /** @var QueueService $queue */
            $queue = ServiceRegister::getService(QueueService::CLASS_NAME);
            $queue->enqueue(
                $configService->getDefaultQueueName(),
                $sendDraftTask,
                $configService->getContext(),
                $sendDraftTask->getPriority()
            );

            if ($sendDraftTask->getExecutionId() !== null) {
                $draftTaskMapService->setExecutionId($orderId, $sendDraftTask->getExecutionId());
            }
        } else {
            $this->enqueueDelayedTask($sendDraftTask, $delayInterval);
        }

        if (!$configService->isFirstShipmentDraftCreated()) {
            $this->enqueueShipmentSchedules();
            $configService->setFirstShipmentDraftCreated();
        }
    }

    /**
     * Returns the status of the CreateDraftTask.
     *
     * @param string $orderId The Order ID.
     *
     * @return ShipmentDraftStatus Entity with correct status and optional failure message.
     */
    public function getDraftStatus($orderId)
    {
        /** @var OrderSendDraftTaskMapService $taskMapService */
        $taskMapService = ServiceRegister::getService(OrderSendDraftTaskMapService::CLASS_NAME);
        $status = new ShipmentDraftStatus();
        $taskMap = $taskMapService->getOrderTaskMap($orderId);

        if ($taskMap === null) {
            $status->status = ShipmentDraftStatus::NOT_QUEUED;
        } elseif ($taskMap->getExecutionId() === null) {
            $status->status = ShipmentDraftStatus::DELAYED;
        } else {
            /** @var QueueService $queue */
            $queue = ServiceRegister::getService(QueueService::CLASS_NAME);
            $task = $queue->find($taskMap->getExecutionId());
            if ($task !== null) {
                $status->status = $task->getStatus();
                $status->message = $task->getFailureDescription();
            } else {
                $status->status = QueueItem::FAILED;
            }
        }

        return $status;
    }

    /**
     * Enqueues delayed send draft task.
     *
     * @param SendDraftTask $task Task to be executed.
     * @param int $delayInterval Interval in minutes to delay the execution.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function enqueueDelayedTask(SendDraftTask $task, $delayInterval)
    {
        /** @var Configuration $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);

        /** @var TimeProvider $timeProvider */
        $timeProvider = ServiceRegister::getService(TimeProvider::CLASS_NAME);
        /** @noinspection PhpUnhandledExceptionInspection */
        $timestamp = $timeProvider->getCurrentLocalTime()
            ->add(new \DateInterval('PT' . $delayInterval . 'M'))
            ->getTimestamp();

        $schedule = new HourlySchedule($task, $configService->getDefaultQueueName(), $configService->getContext());
        $schedule->setMonth((int)date('m', $timestamp));
        $schedule->setDay((int)date('d', $timestamp));
        $schedule->setHour((int)date('H', $timestamp));
        $schedule->setMinute((int)date('i', $timestamp));
        $schedule->setRecurring(false);
        $schedule->setNextSchedule();

        RepositoryRegistry::getRepository(Schedule::CLASS_NAME)->save($schedule);
    }

    /**
     * Enqueues shipment related schedules.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    private function enqueueShipmentSchedules()
    {
        $repository = RepositoryRegistry::getRepository(Schedule::getClassName());
        $firstStart = rand(0, 59);
        $secondStart = ($firstStart + 30) % 60;

        // Schedule hourly task for updating shipment info - start at full hour
        $this->scheduleUpdatePendingShipmentsData($repository, $firstStart);

        // Schedule hourly task for updating shipment info - start at half hour
        $this->scheduleUpdatePendingShipmentsData($repository, $secondStart);

        // Schedule daily task for updating shipment info - start at 11:00 UTC hour
        $this->scheduleUpdateInProgressShipments($repository, 11, rand(0, 59));
    }

    /**
     * Creates hourly task for updating shipment data for pending shipments.
     *
     * @param RepositoryInterface $repository Scheduler repository.
     * @param int $minute Starting minute for the task.
     */
    protected function scheduleUpdatePendingShipmentsData(RepositoryInterface $repository, $minute)
    {
        $hourlyStatuses = array(
            ShipmentStatus::STATUS_PENDING,
        );

        $schedule = new HourlySchedule(
            new UpdateShipmentDataTask($hourlyStatuses),
            $this->getConfigService()->getDefaultQueueName(),
            $this->getConfigService()->getContext()
        );

        $schedule->setMinute($minute);
        $schedule->setNextSchedule();
        $repository->save($schedule);
    }

    /**
     * Creates daily task for updating shipment data for shipments in progress.
     *
     * @param RepositoryInterface $repository Schedule repository.
     * @param int $hour Hour of the day when schedule should be executed.
     * @param int $minute
     */
    protected function scheduleUpdateInProgressShipments(RepositoryInterface $repository, $hour, $minute)
    {
        $dailyStatuses = array(
            ShipmentStatus::STATUS_IN_TRANSIT,
            ShipmentStatus::STATUS_READY,
            ShipmentStatus::STATUS_ACCEPTED,
        );

        $schedule = new DailySchedule(
            new UpdateShipmentDataTask($dailyStatuses),
            $this->getConfigService()->getDefaultQueueName(),
            $this->getConfigService()->getContext()
        );

        $schedule->setHour($hour);
        $schedule->setMinute($minute);
        $schedule->setNextSchedule();

        $repository->save($schedule);
    }

    /**
     * Retrieves config service.
     *
     * @return Configuration | object
     */
    private function getConfigService()
    {
        return ServiceRegister::getService(Configuration::CLASS_NAME);
    }
}
