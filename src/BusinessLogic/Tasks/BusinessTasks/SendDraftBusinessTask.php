<?php

namespace Packlink\BusinessLogic\Tasks\BusinessTasks;

use Exception;
use Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException;
use Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Logeecom\Infrastructure\Http\Exceptions\HttpRequestException;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
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
use Packlink\BusinessLogic\ShipmentDraft\Utility\DraftStatus;
use Packlink\BusinessLogic\Tasks\Interfaces\BusinessTask;
use Packlink\BusinessLogic\Tasks\TaskExecutionConfig;

class SendDraftBusinessTask implements BusinessTask
{
    /**
     * Order unique identifier.
     *
     * @var string
     */
    protected $orderId;

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
    protected $orderShipmentDetailsService;

    /**
     * CustomsService instance.
     *
     * @var CustomsService
     */
    private $customsService;

    /**
     * Optional execution config override.
     *
     * @var TaskExecutionConfig|null
     */
    private $executionConfig;

    /**
     * SendDraftBusinessTask constructor.
     *
     * @param string $orderId Order identifier.
     */
    public function __construct(string $orderId, TaskExecutionConfig $executionConfig = null)
    {
        $this->orderId = $orderId;
        $this->executionConfig = $executionConfig;
    }

    /**
     * Execute business logic: Create shipment draft.
     *
     *
     * @return \Generator
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws \Packlink\BusinessLogic\Http\Exceptions\DraftNotCreatedException
     * @throws OrderNotFound
     * @throws \Packlink\BusinessLogic\OrderShipmentDetails\Exceptions\OrderShipmentDetailsNotFound
     */
    public function execute(): \Generator
    {
        yield 5;

        try {
            $this->markDraftStatus(DraftStatus::PROCESSING);
            yield 10;

            if ($this->shouldNotSynchronize()) {
                $this->logDraftAlreadyCreated();
                $this->markDraftStatus(DraftStatus::COMPLETED);

                yield 100;

                return;
            }

            yield 20;

            $order = $this->getShopOrderService()->getOrderAndShippingData($this->orderId);
            $draft = $this->getOrderService()->prepareDraft($order);

            yield 40;

            $this->tryCreateCustomsInvoice($draft, $order);

            yield 50;

            $reference = $this->sendDraft($draft);

            yield 70;

            $this->getOrderService()->setReference($this->orderId, $reference);

            yield 80;

            $shipment = $this->getProxy()->getShipment($reference);

            if ($shipment) {
                $customsId = isset($draft->customs) ? $draft->customs->customsInvoiceId : '';
                $this->getOrderService()->updateShipmentData($shipment, $customsId);
            }

            yield 90;

            $this->markDraftStatus(DraftStatus::COMPLETED);

            yield 100;
        } catch (EmptyOrderException $e) {
            $this->markDraftStatus(DraftStatus::FAILED, 'Empty order: ' . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            $this->markDraftStatus(DraftStatus::FAILED, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check if draft should not be synchronized (already created).
     *
     * @return bool True if draft already created.
     */
    protected function shouldNotSynchronize()
    {
        $isRepositoryRegistered = RepositoryRegistry::isRegistered(OrderShipmentDetails::getClassName());

        return $isRepositoryRegistered && $this->isDraftCreated($this->orderId);
    }

    /**
     * Create customs invoice for draft if needed.
     *
     * @param Draft $draft Draft object (modified by reference).
     * @param Order $order Order object.
     *
     * @return void
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    private function createCustomsInvoice(Draft $draft, Order $order)
    {
        if (!$this->getCustomsService()->shouldCreateCustoms($draft->to->country, $draft->to->zipCode)) {
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
     * Check if draft has already been created for order.
     *
     * @param string $orderId Order ID.
     *
     * @return bool True if draft created.
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
     * @param string $status
     * @param string|null $error
     *
     * @return void
     */
    private function markDraftStatus($status, $error = null)
    {
        $this->getOrderShipmentDetailsService()->setDraftStatus($this->orderId, $status, $error);
    }

    /**
     * @param Draft $draft
     * @param Order $order
     *
     * @return void
     */
    private function tryCreateCustomsInvoice(Draft $draft, Order $order)
    {
        try {
            $this->createCustomsInvoice($draft, $order);
        } catch (Exception $e) {
            Logger::logWarning(
                'Failed to create customs invoice for order ' . $this->orderId . ' because: ' . $e->getMessage()
            );
        }
    }

    /**
     * @param Draft $draft
     *
     * @return string
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    private function sendDraft(Draft $draft)
    {
        $reference = $this->getProxy()->sendDraft($draft);
        Logger::logInfo(
            'Sent draft shipment for order ' . $this->orderId
            . '. Created reference: ' . $reference
            . '. Draft details: ' . json_encode($draft->toArray())
        );

        return $reference;
    }

    /**
     * @return void
     */
    private function logDraftAlreadyCreated()
    {
        Logger::logInfo("Draft for order [{$this->orderId}] has been already created. Task is terminating.");
    }

    /**
     * Serialize to array (for task executors).
     *
     * @return array Task data.
     */
    public function toArray(): array
    {
        $data = array('orderId' => $this->orderId);

        if ($this->executionConfig !== null) {
            $data['execution_config'] = $this->executionConfig->toArray();
        }

        return $data;
    }

    /**
     * Deserialize from array (for task executors).
     *
     * @param array $data Task data.
     *
     * @return \Packlink\BusinessLogic\Tasks\SendDraftBusinessTask Task instance.
     */
    public static function fromArray(array $data): BusinessTask
    {
        $executionConfig = null;

        if (!empty($data['execution_config']) && is_array($data['execution_config'])) {
            $executionConfig = TaskExecutionConfig::fromArray($data['execution_config']);
        }

        return new self($data['orderId'], $executionConfig);
    }

    /**
     * Get order ID.
     *
     * @return string Order ID.
     */
    public function getOrderId(): string
    {
        return $this->orderId;
    }

    /**
     * Get proxy instance.
     *
     * @return Proxy Proxy instance.
     */
    private function getProxy(): Proxy
    {
        if ($this->proxy === null) {
            $this->proxy = ServiceRegister::getService(Proxy::CLASS_NAME);
        }

        return $this->proxy;
    }

    /**
     * Get order service instance.
     *
     * @return OrderService Order service instance.
     */
    private function getOrderService(): OrderService
    {
        if ($this->orderService === null) {
            $this->orderService = ServiceRegister::getService(OrderService::CLASS_NAME);
        }

        return $this->orderService;
    }

    /**
     * Get order shipment details service.
     *
     * @return OrderShipmentDetailsService Service instance.
     */
    protected function getOrderShipmentDetailsService(): OrderShipmentDetailsService
    {
        if ($this->orderShipmentDetailsService === null) {
            $this->orderShipmentDetailsService = ServiceRegister::getService(OrderShipmentDetailsService::CLASS_NAME);
        }

        return $this->orderShipmentDetailsService;
    }

    /**
     * Get customs service.
     *
     * @return CustomsService Service instance.
     */
    private function getCustomsService(): CustomsService
    {
        if ($this->customsService === null) {
            $this->customsService = ServiceRegister::getService(CustomsService::CLASS_NAME);
        }

        return $this->customsService;
    }

    /**
     * Get shop order service.
     *
     * @return ShopOrderService Service instance.
     */
    private function getShopOrderService(): ShopOrderService
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(ShopOrderService::CLASS_NAME);
    }

    /**
     * @return TaskExecutionConfig|null
     */
    public function getExecutionConfig()
    {
        return $this->executionConfig;
    }
}
