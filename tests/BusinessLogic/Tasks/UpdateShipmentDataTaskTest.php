<?php

namespace Logeecom\Tests\BusinessLogic\Tasks;

use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Infrastructure\Serializer\Serializer;
use Logeecom\Tests\BusinessLogic\BaseSyncTest;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Order\TestOrderRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestHttpClient;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Http\DTO\ParcelInfo;
use Packlink\BusinessLogic\Http\DTO\User;
use Packlink\BusinessLogic\Http\DTO\Warehouse;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\Order\Interfaces\OrderRepository;
use Packlink\BusinessLogic\Order\OrderService;
use Packlink\BusinessLogic\ShippingMethod\Utility\ShipmentStatus;
use Packlink\BusinessLogic\Tasks\UpdateShipmentDataTask;

/**
 * Class UpdateShipmentDataTaskTest
 *
 * @package Logeecom\Tests\BusinessLogic\Tasks
 */
class UpdateShipmentDataTaskTest extends BaseSyncTest
{
    /**
     * @var TestHttpClient
     */
    public $httpClient;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $me = $this;

        $this->httpClient = new TestHttpClient();
        TestServiceRegister::registerService(
            HttpClient::CLASS_NAME,
            function () use ($me) {
                return $me->httpClient;
            }
        );

        TestServiceRegister::registerService(
            OrderService::CLASS_NAME,
            function () {
                return OrderService::getInstance();
            }
        );

        /** @noinspection PhpUnhandledExceptionInspection */
        TestServiceRegister::registerService(
            Proxy::CLASS_NAME,
            function () use ($me) {
                /** @var Configuration $config */
                $config = TestServiceRegister::getService(Configuration::CLASS_NAME);

                return new Proxy($config, $me->httpClient);
            }
        );

        $orderRepository = new TestOrderRepository();

        TestServiceRegister::registerService(
            OrderRepository::CLASS_NAME,
            function () use ($orderRepository) {
                return $orderRepository;
            }
        );

        $this->shopConfig->setDefaultParcel(new ParcelInfo());
        $this->shopConfig->setDefaultWarehouse(new Warehouse());
        $this->shopConfig->setUserInfo(new User());
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        OrderService::resetInstance();

        parent::tearDown();
    }

    public function testExecute()
    {
        $this->httpClient->setMockResponses($this->getMockResponses());
        $this->syncTask->execute();
        self::assertCount(3, $this->eventHistory);
        $this->validate100Progress();

        /** @var \Logeecom\Tests\BusinessLogic\Common\TestComponents\Order\TestOrderRepository $orderRepository */
        $orderRepository = TestServiceRegister::getService(OrderRepository::CLASS_NAME);
        $order = $orderRepository->getOrder('test');

        self::assertEquals(15.85, $order->getBasePrice());
    }

    public function testExecuteStatusShipmentDelivered()
    {
        $this->httpClient->setMockResponses($this->getMockResponsesDelivered());
        $this->syncTask->execute();
        $this->validate100Progress();

        /** @var \Logeecom\Tests\BusinessLogic\Common\TestComponents\Order\TestOrderRepository $orderRepository */
        $orderRepository = TestServiceRegister::getService(OrderRepository::CLASS_NAME);
        $order = $orderRepository->getOrder('test');

        self::assertEquals(15.85, $order->getBasePrice());
        self::assertEquals('delivered', $order->getStatus());
    }

    /**
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound
     */
    public function testAfterInitialFailure()
    {
        /** @var \Logeecom\Tests\BusinessLogic\Common\TestComponents\Order\TestOrderRepository $orderRepository */
        $orderRepository = TestServiceRegister::getService(OrderRepository::CLASS_NAME);
        $orderRepository->shouldThrowGenericException(true);
        $serialized = '';
        try {
            $this->syncTask->execute();
        } catch (\Exception $e) {
            $serialized = Serializer::serialize($this->syncTask);
        }

        $this->httpClient->setMockResponses($this->getMockResponses());
        $orderRepository->shouldThrowGenericException(false);

        $this->syncTask = Serializer::unserialize($serialized);
        $this->attachProgressEventListener();
        $this->syncTask->execute();
        $this->validate100Progress();

        $order = $orderRepository->getOrder('test');

        self::assertEquals(15.85, $order->getBasePrice());
    }

    /**
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound
     */
    public function testAfterOrderNotFoundFailure()
    {
        /** @var \Logeecom\Tests\BusinessLogic\Common\TestComponents\Order\TestOrderRepository $orderRepository */
        $orderRepository = TestServiceRegister::getService(OrderRepository::CLASS_NAME);
        $orderRepository->shouldThrowOrderNotFoundException(true);
        $orderRepository->setIncompleteOrderReferences(array('test1', 'test2'));
        $this->syncTask->execute();
        // there should be 2 order not found messages
        self::assertCount(2, $this->shopLogger->loggedMessages);
        self::assertEquals('Order not found.', $this->shopLogger->loggedMessages[0]->getMessage());
        self::assertEquals('Order not found.', $this->shopLogger->loggedMessages[1]->getMessage());

        // second execute of the same task should not do anything after unserialize
        // because references that are done should be removed from the list
        $this->syncTask = Serializer::unserialize(Serializer::serialize($this->syncTask));
        $this->attachProgressEventListener();
        $this->syncTask->execute();
        $this->validate100Progress();

        self::assertCount(2, $this->shopLogger->loggedMessages);
    }

    public function testAfterProxyFailure()
    {
        /** @var \Logeecom\Tests\BusinessLogic\Common\TestComponents\Order\TestOrderRepository $orderRepository */
        $orderRepository = TestServiceRegister::getService(OrderRepository::CLASS_NAME);
        $orderRepository->setIncompleteOrderReferences(array('test1', 'test2', 'test3'));
        // this sets response only for the first order
        $this->httpClient->setMockResponses($this->getMockResponses());

        $e = null;
        try {
            $this->syncTask->execute();
        } catch (\Exception $e) {
        }

        self::assertNotEmpty($e);
        self::assertCount(0, $this->shopLogger->loggedMessages);
        self::assertCount(2, $this->eventHistory);
        /** @var \Logeecom\Infrastructure\TaskExecution\TaskEvents\TaskProgressEvent $event */
        $event = $this->eventHistory[0];
        // initial progress points
        self::assertEquals(500, $event->getProgressBasePoints());

        // second execute of the same task should take only the third reference and execute it correctly
        $this->syncTask = Serializer::unserialize(Serializer::serialize($this->syncTask));
        $this->attachProgressEventListener();

        $this->httpClient->setMockResponses(
            array(
                new HttpResponse(
                    200, array(), file_get_contents(__DIR__ . '/../Common/ApiResponses/shipment.json')
                ),
                new HttpResponse(
                    200, array(), file_get_contents(__DIR__ . '/../Common/ApiResponses/tracking.json')
                ),
                new HttpResponse(
                    200, array(), file_get_contents(__DIR__ . '/../Common/ApiResponses/shipmentLabels.json')
                ),
            )
        );

        $this->syncTask->execute();

        // task continued - added one more progress and final 100
        self::assertCount(4, $this->eventHistory);

        // no new messages
        self::assertCount(0, $this->shopLogger->loggedMessages);

        $this->validate100Progress();
    }

    /**
     * Tests transformation of the shipment status from Packlink to Core constants.
     */
    public function testShipmentStatus()
    {
        self::assertEquals(ShipmentStatus::STATUS_PENDING, ShipmentStatus::getStatus('AWAITING_COMPLETION'));
        self::assertEquals(ShipmentStatus::STATUS_PENDING, ShipmentStatus::getStatus('READY_TO_PURCHASE'));

        self::assertEquals(ShipmentStatus::STATUS_READY, ShipmentStatus::getStatus('READY_TO_PRINT'));
        self::assertEquals(ShipmentStatus::STATUS_READY, ShipmentStatus::getStatus('READY_FOR_COLLECTION'));
        self::assertEquals(ShipmentStatus::STATUS_READY, ShipmentStatus::getStatus('COMPLETED'));
        self::assertEquals(ShipmentStatus::STATUS_READY, ShipmentStatus::getStatus('CARRIER_OK'));

        self::assertEquals(ShipmentStatus::STATUS_ACCEPTED, ShipmentStatus::getStatus('CARRIER_KO'));
        self::assertEquals(ShipmentStatus::STATUS_ACCEPTED, ShipmentStatus::getStatus('LABELS_KO'));
        self::assertEquals(ShipmentStatus::STATUS_ACCEPTED, ShipmentStatus::getStatus('INTEGRATION_KO'));
        self::assertEquals(ShipmentStatus::STATUS_ACCEPTED, ShipmentStatus::getStatus('PURCHASE_SUCCESS'));
        self::assertEquals(ShipmentStatus::STATUS_ACCEPTED, ShipmentStatus::getStatus('CARRIER_PENDING'));
        self::assertEquals(ShipmentStatus::STATUS_ACCEPTED, ShipmentStatus::getStatus('RETRY'));

        self::assertEquals(ShipmentStatus::STATUS_IN_TRANSIT, ShipmentStatus::getStatus('IN_TRANSIT'));

        self::assertEquals(ShipmentStatus::STATUS_DELIVERED, ShipmentStatus::getStatus('DELIVERED'));
        self::assertEquals(ShipmentStatus::STATUS_DELIVERED, ShipmentStatus::getStatus('RETURNED_TO_SENDER'));
    }

    /**
     * Tests execute with order statuses provided.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpBaseException
     */
    public function testWithOrderStatusesProvided()
    {
        $this->syncTask = new UpdateShipmentDataTask(array(ShipmentStatus::STATUS_IN_TRANSIT));
        $this->attachProgressEventListener();
        $this->httpClient->setMockResponses($this->getMockResponses());
        $this->syncTask->execute();
        self::assertCount(3, $this->eventHistory);
    }

    /**
     * Creates new instance of task that is being tested.
     *
     * @return UpdateShipmentDataTask
     */
    protected function createSyncTaskInstance()
    {
        return new UpdateShipmentDataTask();
    }

    /**
     * Returns responses for testing updating shipment data.
     *
     * @return HttpResponse[] Array of Http responses.
     */
    private function getMockResponses()
    {
        return array(
            new HttpResponse(
                200, array(), file_get_contents(__DIR__ . '/../Common/ApiResponses/shipment.json')
            ),
            new HttpResponse(
                200, array(), file_get_contents(__DIR__ . '/../Common/ApiResponses/tracking.json')
            ),
        );
    }

    /**
     * Returns responses for testing updating shipment data.
     *
     * @return HttpResponse[] Array of Http responses.
     */
    private function getMockResponsesDelivered()
    {
        return array(
            new HttpResponse(
                200, array(), file_get_contents(__DIR__ . '/../Common/ApiResponses/shipmentDelivered.json')
            ),
            new HttpResponse(
                200, array(), file_get_contents(__DIR__ . '/../Common/ApiResponses/shipmentLabels.json')
            ),
            new HttpResponse(
                200, array(), file_get_contents(__DIR__ . '/../Common/ApiResponses/tracking.json')
            ),
        );
    }
}