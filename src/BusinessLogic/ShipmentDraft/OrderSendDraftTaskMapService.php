<?php

namespace Packlink\BusinessLogic\ShipmentDraft;

use Logeecom\Infrastructure\ORM\QueryFilter\Operators;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Packlink\BusinessLogic\BaseService;
use Packlink\BusinessLogic\ShipmentDraft\Exceptions\DraftTaskMapExists;
use Packlink\BusinessLogic\ShipmentDraft\Exceptions\DraftTaskMapNotFound;
use Packlink\BusinessLogic\ShipmentDraft\Models\OrderSendDraftTaskMap;

/**
 * Class OrderSendDraftTaskMapService.
 *
 * @package Packlink\BusinessLogic\ShipmentDraft
 */
class OrderSendDraftTaskMapService extends BaseService
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
     * OrderSendDraftTaskRepository repository.
     *
     * @var OrderSendDraftTaskRepository
     */
    protected $repository;

    /**
     * OrderSendDraftTaskMapService constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->repository = new OrderSendDraftTaskRepository();
    }

    /**
     * Retrieves Order - SendDraftTask map instance.
     *
     * @param string | int $orderId Order id in an integration system.
     *
     * @return OrderSendDraftTaskMap|null An entity for the specified order id, if found.
     */
    public function getOrderTaskMap($orderId)
    {
        return $this->repository->selectByOrderId($orderId);
    }

    /**
     * Returns whether a task associated with the map failed with execution.
     *
     * @param OrderSendDraftTaskMap $orderTaskMap
     *
     * @return bool
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function isMappedTaskFailed($orderTaskMap)
    {
        if ($orderTaskMap->getExecutionId() === null) {
            return false;
        }

        $queueItemRepository = RepositoryRegistry::getRepository(QueueItem::CLASS_NAME);
        $filter = new QueryFilter();
        $filter->where('id', Operators::EQUALS, $orderTaskMap->getExecutionId());
        /** @var QueueItem $item */
        $item = $queueItemRepository->selectOne($filter);

        return $item->getStatus() === QueueItem::FAILED;
    }

    /**
     * Creates new map between order and task execution.
     *
     * @param string $orderId Order ID.
     * @param string $executionId Task execution ID.
     *
     * @throws \Packlink\BusinessLogic\ShipmentDraft\Exceptions\DraftTaskMapExists
     */
    public function createOrderTaskMap($orderId, $executionId = null)
    {
        if ($this->getOrderTaskMap($orderId) !== null) {
            throw new DraftTaskMapExists('A map for given order ID already exists.');
        }

        $orderTaskMap = new OrderSendDraftTaskMap();
        $orderTaskMap->setOrderId($orderId);
        $orderTaskMap->setExecutionId($executionId);

        $this->repository->persist($orderTaskMap);
    }

    /**
     * Sets execution ID of the task to the map shipment reference number.
     *
     * @param string $orderId Order ID.
     * @param string $executionId Task execution ID.
     *
     * @throws \Packlink\BusinessLogic\ShipmentDraft\Exceptions\DraftTaskMapNotFound
     */
    public function setExecutionId($orderId, $executionId)
    {
        $orderTaskMap = $this->getOrderTaskMap($orderId);

        if ($orderTaskMap === null) {
            throw new DraftTaskMapNotFound('A map for given order ID does not exist. Order ID: ' . $orderId);
        }

        $orderTaskMap->setExecutionId($executionId);

        $this->repository->persist($orderTaskMap);
    }
}
