<?php

namespace Packlink\BusinessLogic\ShipmentDraft;

use DateInterval;
use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskExecutorInterface;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Infrastructure\TaskExecution\QueueService;
use Logeecom\Infrastructure\Utility\TimeProvider;
use Packlink\BusinessLogic\BaseService;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\OrderShipmentDetails\OrderShipmentDetailsService;
use Packlink\BusinessLogic\Scheduler\Models\HourlySchedule;
use Packlink\BusinessLogic\Scheduler\Models\Schedule;
use Packlink\BusinessLogic\ShipmentDraft\Objects\ShipmentDraftStatus;
use Packlink\BusinessLogic\ShipmentDraft\Utility\DraftStatus;
use Packlink\BusinessLogic\Tasks\BusinessTasks\SendDraftBusinessTask;
use Packlink\BusinessLogic\Tasks\SendDraftTask;

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
     * @var TaskExecutorInterface
     */
    private $taskExecutor;

    public static function getInstance()
    {
        if (static::$instance === null) {
            $taskExecutor = ServiceRegister::getService(TaskExecutorInterface::CLASS_NAME);
            static::$instance = new static($taskExecutor);
        }

        if (!(static::$instance instanceof static)) {
            throw new \RuntimeException('Wrong static instance of a singleton class.');
        }

        return static::$instance;
    }

    public function __construct(TaskExecutorInterface $taskExecutor)
    {
        parent::__construct();
        $this->taskExecutor = $taskExecutor;
    }

    /**
     * Enqueues the task for creating shipment draft for provided order id.
     * Ensures proper mapping between the order and the created task are persisted.
     *
     * @param string $orderId Shop order id.
     * @param bool $isDelayed Indicates if the execution of the task should be delayed.
     * @param int $delayInterval Interval in minutes to delay the execution.
     *
     * @return void
     */
    public function enqueueCreateShipmentDraftTask($orderId, $isDelayed = false, $delayInterval = 5)
    {
        // ✅ Get current status from OrderShipmentDetails (unified entity)
        $currentStatus = $this->getOrderShipmentDetailsService()->getDraftStatus($orderId);

        // Don't re-enqueue if already pending/processing/completed
        if (in_array($currentStatus, array(DraftStatus::PROCESSING, DraftStatus::COMPLETED), true)) {
            return;
        }

        // Create business task
        $businessTask = new SendDraftBusinessTask($orderId);

        // Enqueue via TaskExecutor interface (platform provides implementation)
        $taskExecutor = $this->taskExecutor;

        if ($isDelayed) {
            $this->getOrderShipmentDetailsService()->setDraftStatus($orderId, DraftStatus::DELAYED);
            $taskExecutor->scheduleDelayed($businessTask, $delayInterval * 60);
        } else {
            $this->getOrderShipmentDetailsService()->setDraftStatus($orderId, DraftStatus::PROCESSING);
            $taskExecutor->enqueue($businessTask);
        }
    }

    /**
     * Returns the status of the CreateDraftTask.
     *
     * ✅ REFACTORED: Gets status directly from OrderShipmentDetails (unified entity).
     *
     * @param string $orderId The Order ID.
     *
     * @return ShipmentDraftStatus Entity with correct status and optional failure message.
     */
    public function getDraftStatus($orderId)
    {
        // ✅ Use OrderShipmentDetailsService for all status operations
        $status = $this->getOrderShipmentDetailsService()->getDraftStatus($orderId);
        $error = $this->getOrderShipmentDetailsService()->getDraftError($orderId);
        $reference = $this->getOrderShipmentDetailsService()->getDraftReference($orderId);

        return (object)array(
            'status' => $status,
            'message' => $error,
            'reference' => $reference
        );
    }

    /**
     * Checks if draft is expired.
     *
     * @param string $reference
     *
     * @return bool
     */
    public function isDraftExpired($reference)
    {
        try {
            $shipment = $this->getProxy()->getShipment($reference);

            if ($shipment) {
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Logger::logError($e->getMessage());

            return false;
        }
    }


    /**
     * ✅ NEW: Get OrderShipmentDetailsService for unified state management.
     *
     * @return OrderShipmentDetailsService Service instance.
     */
    private function getOrderShipmentDetailsService()
    {
        return ServiceRegister::getService(OrderShipmentDetailsService::CLASS_NAME);
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

    /**
     * Retrieves proxy.
     *
     * @return Proxy | object
     */
    private function getProxy()
    {
        return ServiceRegister::getService(Proxy::CLASS_NAME);
    }
}
