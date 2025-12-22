<?php

namespace Logeecom\Tests\BusinessLogic\Tasks;

use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\Serializer\Serializer;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Tests\BusinessLogic\BaseSyncTest;
use Logeecom\Tests\BusinessLogic\CashOnDelivery\TestCashOnDeliveryService;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\TestWarehouse;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Order\TestShopOrderService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\TestRepositoryRegistry;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\CashOnDelivery\Interfaces\CashOnDeliveryServiceInterface;
use Packlink\BusinessLogic\CashOnDelivery\Model\CashOnDelivery;
use Packlink\BusinessLogic\Http\DTO\ParcelInfo;
use Packlink\BusinessLogic\Http\DTO\User;
use Packlink\BusinessLogic\Order\Interfaces\ShopOrderService;
use Packlink\BusinessLogic\Order\OrderService;
use Packlink\BusinessLogic\OrderShipmentDetails\Models\OrderShipmentDetails;
use Packlink\BusinessLogic\OrderShipmentDetails\OrderShipmentDetailsService;
use Packlink\BusinessLogic\ShippingMethod\Utility\ShipmentStatus;
use Packlink\BusinessLogic\Tasks\UpdateShipmentDataTask;

/**
 * Class UpdateShipmentDataTaskTest
 *
 * @package Logeecom\Tests\BusinessLogic\Tasks
 * @property UpdateShipmentDataTask $syncTask
 */
class UpdateShipmentDataTaskTest extends BaseSyncTest
{
    /**
     * @var \Packlink\BusinessLogic\OrderShipmentDetails\OrderShipmentDetailsService
     */
    public $orderShipmentDetailsService;

    /** @var TestCashOnDeliveryService $cashOnDeliveryService*/

    private $cashOnDeliveryService;

    /**
     * @before
     * @inheritdoc
     */
    public function before()
    {
        parent::before();

        $me = $this;

        TestRepositoryRegistry::registerRepository(
            OrderShipmentDetails::getClassName(),
            MemoryRepository::getClassName()
        );

        /** @noinspection PhpUnhandledExceptionInspection */
        RepositoryRegistry::registerRepository(CashOnDelivery::CLASS_NAME, MemoryRepository::getClassName());

        $this->cashOnDeliveryService = new TestCashOnDeliveryService();
        ServiceRegister::registerService(
            CashOnDeliveryServiceInterface::CLASS_NAME,
            function () use ($me) {
                return $me->cashOnDeliveryService;
            }
        );


        TestServiceRegister::registerService(
            OrderService::CLASS_NAME,
            function () {
                return OrderService::getInstance();
            }
        );

        $this->orderShipmentDetailsService = OrderShipmentDetailsService::getInstance();

        TestServiceRegister::registerService(
            OrderShipmentDetailsService::CLASS_NAME,
            function () use ($me) {
                return $me->orderShipmentDetailsService;
            }
        );

        $shopOrderService = new TestShopOrderService();

        TestServiceRegister::registerService(
            ShopOrderService::CLASS_NAME,
            function () use ($shopOrderService) {
                return $shopOrderService;
            }
        );

        $this->shopConfig->setDefaultParcel(new ParcelInfo());
        $this->shopConfig->setDefaultWarehouse(new TestWarehouse());
        $this->shopConfig->setUserInfo(new User());
    }

    /**
     * @after
     * @inheritdoc
     */
    protected function after()
    {
        OrderService::resetInstance();

        parent::after();
    }

    /**
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpBaseException
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound
     */
    public function testExecute()
    {
        $this->orderShipmentDetailsService->setReference('test_order_id', 'test');
        $this->httpClient->setMockResponses($this->getMockResponses());
        $this->syncTask->execute();
        self::assertCount(3, $this->eventHistory);
        $this->validate100Progress();

        /** @var \Logeecom\Tests\BusinessLogic\Common\TestComponents\Order\TestShopOrderService $shopOrderService */
        $shopOrderService = TestServiceRegister::getService(ShopOrderService::CLASS_NAME);
        $order = $shopOrderService->getOrder('test_order_id');

        self::assertEquals(ShipmentStatus::STATUS_READY, $order->getStatus());
    }

    /**
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpBaseException
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound
     */
    public function testExecuteStatusShipmentDelivered()
    {
        $this->orderShipmentDetailsService->setReference('test_order_id', 'test');
        $this->httpClient->setMockResponses($this->getMockResponsesDelivered());
        $this->syncTask->execute();
        $this->validate100Progress();

        /** @var TestShopOrderService $shopOrderService */
        $shopOrderService = TestServiceRegister::getService(ShopOrderService::CLASS_NAME);
        $order = $shopOrderService->getOrder('test_order_id');

        self::assertEquals(ShipmentStatus::STATUS_DELIVERED, $order->getStatus());
    }

    /**
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpBaseException
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound
     */
    public function testAfterInitialFailure()
    {
        $this->orderShipmentDetailsService->setReference('test_order_id', 'test');

        /** @var TestShopOrderService $shopOrderService */
        $shopOrderService = TestServiceRegister::getService(ShopOrderService::CLASS_NAME);
        $shopOrderService->shouldThrowGenericException(true);
        $serialized = '';
        try {
            $this->syncTask->execute();
        } catch (\Exception $e) {
            $serialized = Serializer::serialize($this->syncTask);
        }

        $this->httpClient->setMockResponses($this->getMockResponses());
        $shopOrderService->shouldThrowGenericException(false);

        $this->syncTask = Serializer::unserialize($serialized);
        $this->attachProgressEventListener();
        $this->syncTask->execute();
        $this->validate100Progress();

        // when the task breaks for a specific reference, that reference will not be updated again.
        $order = $shopOrderService->getOrder('test_order_id');
        self::assertNull($order->getShippingPrice());
    }

    /**
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpBaseException
     */
    public function testAfterOrderNotFoundFailure()
    {
        $this->orderShipmentDetailsService->setReference('test_order_1', 'test');
        $this->httpClient->setMockResponses($this->getMockResponses());

        /** @var TestShopOrderService $shopOrderService */
        $shopOrderService = TestServiceRegister::getService(ShopOrderService::CLASS_NAME);
        $shopOrderService->shouldThrowOrderNotFoundException(true);
        $this->syncTask->execute();

        self::assertCount(2, $this->shopLogger->loggedMessages);
        self::assertEquals('Order not found.', $this->shopLogger->loggedMessages[1]->getMessage());

        // second execute of the same task should not do anything after unserialize
        // because references that are done should be removed from the list
        $this->syncTask = Serializer::unserialize(Serializer::serialize($this->syncTask));
        $this->attachProgressEventListener();
        $this->syncTask->execute();
        $this->validate100Progress();

        self::assertCount(2, $this->shopLogger->loggedMessages);
    }

    /**
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpBaseException
     */
    public function testAfterProxyFailure()
    {
        $this->orderShipmentDetailsService->setReference('test_order_1', 'test');
        $this->orderShipmentDetailsService->setReference('test_order_2', 'test2');
        $this->orderShipmentDetailsService->setReference('test_order_3', 'test');
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
     * @throws \Packlink\BusinessLogic\OrderShipmentDetails\Exceptions\OrderShipmentDetailsNotFound
     */
    public function testWithOrderStatusesProvided()
    {
        $this->orderShipmentDetailsService->setReference('test_order_1', 'test');
        $this->orderShipmentDetailsService->setShippingStatus('test', ShipmentStatus::STATUS_IN_TRANSIT);
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
