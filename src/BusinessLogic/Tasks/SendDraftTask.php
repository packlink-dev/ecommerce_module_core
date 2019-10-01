<?php

namespace Packlink\BusinessLogic\Tasks;

use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\Serializer\Serializer;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Task;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\Order\OrderService;

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
     */
    public function execute()
    {
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
}
