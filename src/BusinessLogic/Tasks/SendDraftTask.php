<?php

namespace Packlink\BusinessLogic\Tasks;

use Exception;
use Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException;
use Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Logeecom\Infrastructure\Http\Exceptions\HttpRequestException;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\Serializer\Serializer;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Exceptions\AbortTaskExecutionException;
use Logeecom\Infrastructure\TaskExecution\Interfaces\Priority;
use Logeecom\Infrastructure\TaskExecution\Task;
use Packlink\BusinessLogic\Customs\CustomsService;
use Packlink\BusinessLogic\Http\DTO\Draft;
use Packlink\BusinessLogic\Http\DTO\Draft\Customs;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\Order\Exceptions\EmptyOrderException;
use Packlink\BusinessLogic\Order\Exceptions\OrderNotFound;
use Packlink\BusinessLogic\Order\Interfaces\ShopOrderService;
use Packlink\BusinessLogic\Order\Objects\Order;
use Packlink\BusinessLogic\Order\OrderService;
use Packlink\BusinessLogic\OrderShipmentDetails\Models\OrderShipmentDetails;
use Packlink\BusinessLogic\OrderShipmentDetails\OrderShipmentDetailsService;
use Packlink\BusinessLogic\ShipmentDraft\OrderSendDraftTaskMapService;

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
     * @var CustomsService
     */
    private $customsService;

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
     * @inheritDoc
     */
    public function __serialize()
    {
        return $this->toArray();
    }

    /**
     * @inheritDoc
     */
    public function __unserialize($data)
    {
        $this->orderId = $data['order_id'];
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
     * Retrieves task priority.
     *
     * @return int Task priority.
     */
    public function getPriority()
    {
        return Priority::HIGH;
    }

    /**
     * Runs task logic.
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws \Packlink\BusinessLogic\Http\Exceptions\DraftNotCreatedException
     * @throws OrderNotFound
     * @throws \Packlink\BusinessLogic\OrderShipmentDetails\Exceptions\OrderShipmentDetailsNotFound
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\AbortTaskExecutionException
     */
    public function execute()
    {
        $this->setExecution();

        Logger::logInfo('Send draft task with customs started');

        $isRepositoryRegistered = RepositoryRegistry::isRegistered(OrderShipmentDetails::getClassName());
        if ($isRepositoryRegistered && $this->isDraftCreated($this->orderId)) {
            Logger::logInfo("Draft for order [{$this->orderId}] has been already created. Task is terminating.");
            $this->reportProgress(100);

            return;
        }

        try {
            $order = $this->getShopOrderService()->getOrderAndShippingData($this->orderId);
            $draft = $this->getOrderService()->prepareDraft($order);
        } catch (EmptyOrderException $e) {
            throw new AbortTaskExecutionException($e->getMessage());
        }

        try {
            $this->createCustomsInvoice($draft, $order);
        } catch (Exception $e) {
            Logger::logInfo('Failed to create customs invoice for order ' . $this->orderId
                . 'because: ' . $e->getMessage());
        }

        $this->reportProgress(35);

        $reference = $this->getProxy()->sendDraft($draft);
        Logger::logInfo(
            'Sent draft shipment for order ' . $this->orderId
            . '. Created reference: ' . $reference
            . '. Draft details: ' . json_encode($draft->toArray())
        );
        $this->reportProgress(85);

        $this->getOrderService()->setReference($this->orderId, $reference);
        $shipment = $this->getProxy()->getShipment($reference);

        if ($shipment) {
            $this->getOrderService()->updateShipmentData($shipment, isset($draft->customs) ? $draft->customs->customsInvoiceId : '');
        }

        $this->reportProgress(100);
    }

    /**
     * @param Draft $draft
     *
     * @return void
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws OrderNotFound
     */
    private function createCustomsInvoice(Draft &$draft, Order $order)
    {
        if (!$this->getCustomsService()->isShipmentInternational($draft->to->country, $draft->to->zipCode)) {
            return;
        }

        $customsId = $this->getCustomsService()->sendCustomsInvoice($order);

        if (!$customsId) {
            return;
        }

        $draft->hasCustoms = true;
        $draft->customs = new Customs();
        $draft->customs->customsInvoiceId = $customsId;
    }

    /**
     * Checks whether draft has already been created for a particular order.
     *
     * @param string $orderId Order id in an integrated system.
     *
     * @return boolean Returns TRUE if draft has been created; FALSE otherwise.
     */
    private function isDraftCreated($orderId)
    {
        $shipmentDetails = $this->getOrderShipmentDetailsService()->getDetailsByOrderId($orderId);

        if ($shipmentDetails === null) {
            return false;
        }

        $reference = $shipmentDetails->getReference();

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

    /**
     * Retrieves customs service.
     *
     * @return CustomsService
     */
    private function getCustomsService()
    {
        if ($this->customsService === null) {
            $this->customsService = ServiceRegister::getService(CustomsService::CLASS_NAME);
        }

        return $this->customsService;
    }

    /**
     * @return ShopOrderService
     */
    private function getShopOrderService()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(ShopOrderService::CLASS_NAME);
    }

    /**
     * Sets task execution Id to the order draft task map, if needed.
     *
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function setExecution()
    {
        /** @var OrderSendDraftTaskMapService $taskMapService */
        $taskMapService = ServiceRegister::getService(OrderSendDraftTaskMapService::CLASS_NAME);
        $taskMap = $taskMapService->getOrderTaskMap($this->orderId);
        if ($taskMap === null) {
            $taskMapService->createOrderTaskMap($this->orderId, $this->getExecutionId());
        } else {
            if (!$taskMap->getExecutionId()) {
                $taskMapService->setExecutionId($this->orderId, $this->getExecutionId());
            }
        }
    }
}
