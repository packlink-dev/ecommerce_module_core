<?php

namespace Packlink\BusinessLogic\Tasks;

use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\Serializer\Serializer;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Task;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\Order\Interfaces\ShopOrderService;
use Packlink\BusinessLogic\Order\OrderService;
use Packlink\BusinessLogic\OrderShipmentDetails\Exceptions\OrderShipmentDetailsNotFound;
use Packlink\BusinessLogic\OrderShipmentDetails\OrderShipmentDetailsService;

/**
 * Class UpdateShipmentDataTask.
 *
 * @package Packlink\BusinessLogic\Tasks
 *
 * @deprecated The webhooks must be utilized to track shipment data updates.
 */
class UpdateShipmentDataTask extends Task
{
    /**
     * Current task progress.
     *
     * @var float
     */
    protected $progress = 0;
    /**
     * Progress step.
     *
     * @var float
     */
    protected $progressStep = 0;
    /**
     * An array of references to be updated.
     *
     * @var array
     */
    protected $references = array();
    /**
     * List of order statuses that should be used when retrieving references to check for updates.
     *
     * @var array
     */
    protected $orderStatuses = array();
    /**
     * @var \Packlink\BusinessLogic\Order\Interfaces\ShopOrderService
     */
    private $shopOrderService;
    /**
     * Order service instance.
     *
     * @var OrderService
     */
    private $orderService;
    /**
     * Order shipment details service.
     *
     * @var OrderShipmentDetailsService
     */
    private $orderDetailsService;
    /**
     * Proxy instance.
     *
     * @var Proxy
     */
    private $proxy;

    /**
     * UpdateShipmentDataTask constructor.
     *
     * @param array $orderStatuses
     */
    public function __construct(array $orderStatuses = array())
    {
        $this->orderStatuses = $orderStatuses;
    }

    /**
     * Transforms array into an serializable object,
     *
     * @param array $array Data that is used to instantiate serializable object.
     *
     * @return \Logeecom\Infrastructure\Serializer\Interfaces\Serializable
     *      Instance of serialized object.
     */
    public static function fromArray(array $array)
    {
        $entity = new static();

        $entity->progress = $array['progress'];
        $entity->progressStep = $array['progress_step'];
        $entity->references = $array['references'];
        $entity->orderStatuses = isset($array['order_statues']) ? $array['order_statues'] : array();

        return $entity;
    }

    /**
     * Transforms serializable object into an array.
     *
     * @return array Array representation of a serializable object.
     */
    public function toArray()
    {
        return  array(
            'progress' => $this->progress,
            'progress_step' => $this->progressStep,
            'references' => $this->references,
            'order_statuses' => $this->orderStatuses,
        );
    }

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return Serializer::serialize(
            array(
                $this->references,
                $this->progress,
                $this->progressStep,
                $this->orderStatuses,
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        $original = Serializer::unserialize($serialized);

        if (count($original) === 3) {
            list($this->references, $this->progress, $this->progressStep) = $original;
        } else {
            list($this->references, $this->progress, $this->progressStep, $this->orderStatuses) = $original;
        }
    }

    /**
     * Runs task logic.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpBaseException
     */
    public function execute()
    {
        if (empty($this->references) && $this->progress === 0) {
            $this->initializeState($this->orderStatuses);
        }

        while (!empty($this->references)) {
            $orderReference = array_shift($this->references);
            try {
                $this->updateOrderShipmentData($orderReference);
            } catch (OrderShipmentDetailsNotFound $e) {
                Logger::logWarning($e->getMessage());
            }

            $this->progress += $this->progressStep;
            $this->reportProgress($this->progress);
        }

        $this->reportProgress(100);
    }

    /**
     * Initializes needed parameters for the task execution.
     *
     * @param array $orderStatuses List of order statuses.
     */
    protected function initializeState(array $orderStatuses = array())
    {
        if (empty($orderStatuses)) {
            $this->references = $this->getOrderDetailsService()->getIncompleteOrderReferences();
        } else {
            $this->references = $this->getOrderDetailsService()->getOrderReferencesWithStatus($orderStatuses);
        }

        $this->progress = 5;
        $this->reportProgress($this->progress);

        $total = count($this->references);
        if ($total > 0) {
            $this->progressStep = 95 / $total;
        }
    }

    /**
     * Performs the task on a single shipment.
     *
     * @param string $reference Shipment reference
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpBaseException
     * @throws \Packlink\BusinessLogic\OrderShipmentDetails\Exceptions\OrderShipmentDetailsNotFound
     */
    protected function updateOrderShipmentData($reference)
    {
        $shipmentDetailsService = $this->getOrderDetailsService();
        if (!$shipmentDetailsService->isShipmentDeleted($reference)) {
            $shipment = $this->getProxy()->getShipment($reference);
            if ($shipment !== null) {
                $this->getOrderService()->updateShipmentData($shipment);
            } else {
                $shipmentDetailsService->markShipmentDeleted($reference);
            }
        }
    }

    /**
     * Gets the order repository instance.
     *
     * @return ShopOrderService Order repository instance.
     */
    protected function getShopOrderService()
    {
        if ($this->shopOrderService === null) {
            $this->shopOrderService = ServiceRegister::getService(ShopOrderService::CLASS_NAME);
        }

        return $this->shopOrderService;
    }

    /**
     * Gets the order repository instance.
     *
     * @return OrderShipmentDetailsService Order repository instance.
     */
    protected function getOrderDetailsService()
    {
        if ($this->orderDetailsService === null) {
            $this->orderDetailsService = ServiceRegister::getService(OrderShipmentDetailsService::CLASS_NAME);
        }

        return $this->orderDetailsService;
    }

    /**
     * Returns proxy instance.
     *
     * @return Proxy Proxy instance.
     */
    protected function getProxy()
    {
        if ($this->proxy === null) {
            $this->proxy = ServiceRegister::getService(Proxy::CLASS_NAME);
        }

        return $this->proxy;
    }

    /**
     * Returns order service instance.
     *
     * @return OrderService Order service instance.
     */
    protected function getOrderService()
    {
        if ($this->orderService === null) {
            $this->orderService = ServiceRegister::getService(OrderService::CLASS_NAME);
        }

        return $this->orderService;
    }
}
