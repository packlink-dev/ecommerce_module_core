<?php

namespace Packlink\BusinessLogic\Tasks;

use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\Serializer\Serializer;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Task;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\Order\Exceptions\OrderNotFound;
use Packlink\BusinessLogic\Order\Interfaces\OrderRepository;
use Packlink\BusinessLogic\Order\OrderService;
use Packlink\BusinessLogic\ShippingMethod\Utility\ShipmentStatus;

/**
 * Class UpdateShipmentDataTask.
 *
 * @package Packlink\BusinessLogic\Tasks
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
     * @var \Packlink\BusinessLogic\Order\Interfaces\OrderRepository
     */
    private $orderRepository;
    /**
     * Order service instance.
     *
     * @var OrderService
     */
    private $orderService;
    /**
     * Proxy instance.
     *
     * @var Proxy
     */
    private $proxy;

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
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        list($this->references, $this->progress, $this->progressStep) = Serializer::unserialize($serialized);
    }

    /**
     * Runs task logic.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpBaseException
     */
    public function execute()
    {
        if (empty($this->references) && $this->progress === 0) {
            $this->initializeState();
        }

        while (!empty($this->references)) {
            $orderReference = array_shift($this->references);
            try {
                $this->updateOrderShipmentData($orderReference);
            } catch (OrderNotFound $e) {
                Logger::logWarning($e->getMessage());
            }

            $this->progress += $this->progressStep;
            $this->reportProgress($this->progress);
        }

        $this->reportProgress(100);
    }

    /**
     * Initializes needed parameters for the task execution.
     */
    protected function initializeState()
    {
        $this->references = $this->getOrderRepository()->getIncompleteOrderReferences();

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
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound
     */
    protected function updateOrderShipmentData($reference)
    {
        $orderRepository = $this->getOrderRepository();
        if (!$orderRepository->isShipmentDeleted($reference)) {
            $shipment = $this->getProxy()->getShipment($reference);
            if ($shipment !== null) {
                $orderService = $this->getOrderService();
                $orderService->updateTrackingInfo($shipment);
                $orderService->updateShipmentLabel($shipment);
                $orderService->updateShippingStatus($shipment, ShipmentStatus::getStatus($shipment->status));

                $orderRepository->setShippingPriceByReference($reference, (float)$shipment->price);
            } else {
                $orderRepository->markShipmentDeleted($reference);
            }
        }
    }

    /**
     * Gets the order repository instance.
     *
     * @return OrderRepository Order repository instance.
     */
    protected function getOrderRepository()
    {
        if ($this->orderRepository === null) {
            $this->orderRepository = ServiceRegister::getService(OrderRepository::CLASS_NAME);
        }

        return $this->orderRepository;
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
