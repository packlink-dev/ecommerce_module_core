<?php

namespace Packlink\BusinessLogic\Tasks;

use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\Serializer\Serializer;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Task;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\Order\Models\OrderShipmentDetails;
use Packlink\BusinessLogic\Order\OrderService;
use Packlink\BusinessLogic\OrderShipmentDetails\OrderShipmentDetailsService;

/**
 * Class UploadDraftTask
 * @package Packlink\BusinessLogic\Tasks
 */
class SendDraftTask extends Task
{
    /**
     * Order unique identifier.
     *
     * @var string
     */
    private $orderId;
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
     * OrderShipmentDetailsService instance.
     *
     * @var OrderShipmentDetailsService
     */
    private $orderShipmentDetailsService;

    /**
     * UploadDraftTask constructor.
     *
     * @param string $orderId Order identifier.
     */
    public function __construct($orderId)
    {
        $this->orderId = $orderId;
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
        return new static($array['order_id']);
    }

    /**
     * Transforms serializable object into an array.
     *
     * @return array Array representation of a serializable object.
     */
    public function toArray()
    {
        return array('order_id' => $this->orderId);
    }

    /**
     * String representation of object
     *
     * @return string the string representation of the object or null
     */
    public function serialize()
    {
        return Serializer::serialize(array($this->orderId));
    }

    /**
     * Constructs the object
     *
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     *
     * @return void
     */
    public function unserialize($serialized)
    {
        list($this->orderId) = Serializer::unserialize($serialized);
    }

    /**
     * Runs task logic.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function execute()
    {
        $isRepositoryRegistered = RepositoryRegistry::isRegistered(OrderShipmentDetails::getClassName());
        if ($isRepositoryRegistered && $this->isDraftCreated($this->orderId)) {
            Logger::logWarning("Draft for order [{$this->orderId}] has been already created. Task is terminating.");
            $this->reportProgress(100);
            return;
        }

        $draft = $this->getOrderService()->prepareDraft($this->orderId);
        $this->reportProgress(35);

        $reference = $this->getProxy()->sendDraft($draft);
        Logger::logInfo(
            'Sent draft shipment for order ' . $this->orderId
            . '. Created reference: ' . $reference
            . '. Draft details: ' . json_encode($draft->toArray())
        );
        $this->reportProgress(85);

        $this->getOrderService()->setReference($this->orderId, $reference);
        $this->reportProgress(100);
    }

    /**
     * Checks whether draft has already been created for a particular order.
     *
     * @param string $orderId Order id in an integrated system.
     *
     * @return boolean Returns TRUE if draft has been created; FALSE otherwise.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    private function isDraftCreated($orderId)
    {
        $draft = $this->getOrderShipmentDetailsService()->getDetailsByOrderId($orderId);

        if ($draft === null) {
            return false;
        }

        $reference = $draft->getReference();

        return !empty($reference);
    }

    /**
     * Returns proxy instance.
     *
     * @return Proxy Proxy instance.
     */
    private function getProxy()
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
    private function getOrderService()
    {
        if ($this->orderService === null) {
            $this->orderService = ServiceRegister::getService(OrderService::CLASS_NAME);
        }

        return $this->orderService;
    }

    /**
     * Retrieves order-shipment details service.
     *
     * @return OrderShipmentDetailsService Service instance.
     */
    private function getOrderShipmentDetailsService()
    {
        if ($this->orderShipmentDetailsService === null) {
            $this->orderShipmentDetailsService = ServiceRegister::getService(OrderShipmentDetailsService::CLASS_NAME);
        }

        return $this->orderShipmentDetailsService;
    }
}
