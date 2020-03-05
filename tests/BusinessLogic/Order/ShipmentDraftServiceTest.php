<?php

namespace Logeecom\Tests\BusinessLogic\Order;

use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Infrastructure\TaskExecution\QueueService;
use Logeecom\Infrastructure\Utility\TimeProvider;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\TestWarehouse;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Order\TestShopOrderService;
use Logeecom\Tests\BusinessLogic\ShippingMethod\TestShopShippingMethodService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryQueueItemRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\TestRepositoryRegistry;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestQueueService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestTaskRunnerWakeupService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestHttpClient;
use Logeecom\Tests\Infrastructure\Common\TestComponents\Utility\TestTimeProvider;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Http\DTO\ParcelInfo;
use Packlink\BusinessLogic\Http\DTO\User;
use Packlink\BusinessLogic\Order\Interfaces\ShopOrderService;
use Packlink\BusinessLogic\Order\OrderService;
use Packlink\BusinessLogic\OrderShipmentDetails\Models\OrderShipmentDetails;
use Packlink\BusinessLogic\OrderShipmentDetails\OrderShipmentDetailsService;
use Packlink\BusinessLogic\Scheduler\Models\Schedule;
use Packlink\BusinessLogic\ShipmentDraft\Models\OrderSendDraftTaskMap;
use Packlink\BusinessLogic\ShipmentDraft\Objects\ShipmentDraftStatus;
use Packlink\BusinessLogic\ShipmentDraft\OrderSendDraftTaskMapService;
use Packlink\BusinessLogic\ShipmentDraft\ShipmentDraftService;
use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\ShippingMethod\PackageTransformer;
use Packlink\BusinessLogic\ShippingMethod\ShippingMethodService;

/**
 * Class ShipmentDraftServiceTest.
 *
 * @package Logeecom\Tests\BusinessLogic\Order
 */
class ShipmentDraftServiceTest extends BaseTestWithServices
{
    /**
     * @var ShipmentDraftService
     */
    public $draftShipmentService;
    /**
     * @var OrderSendDraftTaskMapService
     */
    public $orderSendDraftTaskMapService;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $me = $this;
        TestRepositoryRegistry::registerRepository(OrderShipmentDetails::CLASS_NAME, MemoryRepository::getClassName());
        TestRepositoryRegistry::registerRepository(OrderSendDraftTaskMap::CLASS_NAME, MemoryRepository::getClassName());
        TestRepositoryRegistry::registerRepository(Schedule::CLASS_NAME, MemoryRepository::getClassName());
        TestRepositoryRegistry::registerRepository(QueueItem::CLASS_NAME, MemoryQueueItemRepository::getClassName());
        TestRepositoryRegistry::registerRepository(
            ShippingMethod::CLASS_NAME,
            MemoryQueueItemRepository::getClassName()
        );

        $timeProvider = new TestTimeProvider();
        $timeProvider->setCurrentLocalTime(new \DateTime('2019-10-18 17:55:00'));

        TestServiceRegister::registerService(
            TimeProvider::CLASS_NAME,
            function () use ($timeProvider) {
                return $timeProvider;
            }
        );

        TestServiceRegister::registerService(
            OrderShipmentDetailsService::CLASS_NAME,
            function () {
                return OrderShipmentDetailsService::getInstance();
            }
        );

        $me->draftShipmentService = ShipmentDraftService::getInstance();
        TestServiceRegister::registerService(
            ShipmentDraftService::CLASS_NAME,
            function () use ($me) {
                return $me->draftShipmentService;
            }
        );

        $me->orderSendDraftTaskMapService = OrderSendDraftTaskMapService::getInstance();
        TestServiceRegister::registerService(
            OrderSendDraftTaskMapService::CLASS_NAME,
            function () use ($me) {
                return $me->orderSendDraftTaskMapService;
            }
        );

        TestServiceRegister::registerService(
            QueueService::CLASS_NAME,
            function () {
                return new TestQueueService();
            }
        );

        TestServiceRegister::registerService(
            TaskRunnerWakeup::CLASS_NAME,
            function () {
                return new TestTaskRunnerWakeupService();
            }
        );

        TestServiceRegister::registerService(
            ShopOrderService::CLASS_NAME,
            function () {
                return new TestShopOrderService();
            }
        );

        TestServiceRegister::registerService(
            PackageTransformer::CLASS_NAME,
            function () {
                return PackageTransformer::getInstance();
            }
        );

        TestServiceRegister::registerService(
            ShopShippingMethodService::CLASS_NAME,
            function () {
                return new TestShopShippingMethodService();
            }
        );

        TestServiceRegister::registerService(
            ShippingMethodService::CLASS_NAME,
            function () {
                return ShippingMethodService::getInstance();
            }
        );

        TestServiceRegister::registerService(
            OrderService::CLASS_NAME,
            function () {
                return OrderService::getInstance();
            }
        );

        $this->shopConfig->setDefaultParcel(ParcelInfo::defaultParcel());
        $this->shopConfig->setDefaultWarehouse(new TestWarehouse());
        $this->shopConfig->setUserInfo(new User());
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        OrderShipmentDetailsService::resetInstance();
        ShipmentDraftService::resetInstance();

        parent::tearDown();
    }

    /**
     * Tests creating shipment draft task.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Packlink\BusinessLogic\ShipmentDraft\Exceptions\DraftTaskMapExists
     * @throws \Packlink\BusinessLogic\ShipmentDraft\Exceptions\DraftTaskMapNotFound
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function testCreateDraft()
    {
        $this->draftShipmentService->enqueueCreateShipmentDraftTask('test');

        $draftStatus = $this->draftShipmentService->getDraftStatus('test');

        $this->assertEquals(QueueItem::QUEUED, $draftStatus->status);
        $this->assertEmpty($draftStatus->message);
    }

    /**
     * Tests creating delayed task.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Packlink\BusinessLogic\ShipmentDraft\Exceptions\DraftTaskMapExists
     * @throws \Packlink\BusinessLogic\ShipmentDraft\Exceptions\DraftTaskMapNotFound
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function testCreateDelayedDraft()
    {
        /** @var TimeProvider $timeProvider */
        $timeProvider = ServiceRegister::getService(TimeProvider::CLASS_NAME);
        $now = $timeProvider->getCurrentLocalTime();
        $delay = 8;

        $this->draftShipmentService->enqueueCreateShipmentDraftTask('test', true, $delay);

        $draftStatus = $this->draftShipmentService->getDraftStatus('test');

        $this->assertEquals(ShipmentDraftStatus::DELAYED, $draftStatus->status);
        $this->assertEmpty($draftStatus->message);

        $repository = RepositoryRegistry::getRepository(Schedule::CLASS_NAME);
        /** @var Schedule[] $schedules */
        $schedules = $repository->select();

        $this->assertCount(1, $schedules);
        $this->assertInstanceOf('\\Packlink\\BusinessLogic\\Tasks\\SendDraftTask', $schedules[0]->getTask());
        $this->assertEquals($delay * 60, $schedules[0]->getNextSchedule()->getTimestamp() - $now->getTimestamp());
    }

    /**
     * Tests idempotent operation for creating send draft shipment task.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Packlink\BusinessLogic\ShipmentDraft\Exceptions\DraftTaskMapExists
     * @throws \Packlink\BusinessLogic\ShipmentDraft\Exceptions\DraftTaskMapNotFound
     */
    public function testDoubleCreate()
    {
        $this->draftShipmentService->enqueueCreateShipmentDraftTask('test');

        $map = $this->orderSendDraftTaskMapService->getOrderTaskMap('test');
        $this->assertNotEmpty($map->getExecutionId());

        // draft task should not be created twice
        $this->draftShipmentService->enqueueCreateShipmentDraftTask('test');
        $map2 = $this->orderSendDraftTaskMapService->getOrderTaskMap('test');

        $this->assertEquals($map->getId(), $map2->getId());
    }

    /**
     * Tests creating shipment details object.
     */
    public function testStatusNotCreated()
    {
        $draftStatus = $this->draftShipmentService->getDraftStatus('test');

        $this->assertEquals(ShipmentDraftStatus::NOT_QUEUED, $draftStatus->status);
        $this->assertEmpty($draftStatus->message);
    }

    /**
     * Tests fail task messages.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\AbortTaskExecutionException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Packlink\BusinessLogic\ShipmentDraft\Exceptions\DraftTaskMapExists
     * @throws \Packlink\BusinessLogic\ShipmentDraft\Exceptions\DraftTaskMapNotFound
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function testStatusFailed()
    {
        /** @var TestHttpClient $httpClient */
        $httpClient = ServiceRegister::getService(HttpClient::CLASS_NAME);
        $httpClient->setMockResponses($this->getMockResponses());

        $this->draftShipmentService->enqueueCreateShipmentDraftTask('test');

        $map = $this->orderSendDraftTaskMapService->getOrderTaskMap('test');

        /** @var QueueService $queueService */
        $queueService = TestServiceRegister::getService(QueueService::CLASS_NAME);
        $queueItem = $queueService->find($map->getExecutionId());
        $queueService->start($queueItem);
        $queueItem->setRetries(6);
        $queueService->fail($queueItem, 'Error in task.');

        $draftStatus = $this->draftShipmentService->getDraftStatus('test');

        $this->assertEquals(QueueItem::FAILED, $draftStatus->status);
        $this->assertEquals('Attempt 7: Error in task.', $draftStatus->message);
    }

    /**
     * Returns responses for testing sending of shipment draft.
     *
     * @return HttpResponse[] Array of Http responses.
     */
    private function getMockResponses()
    {
        return array(
            // send draft response
            new HttpResponse(
                200, array(), file_get_contents(__DIR__ . '/../Common/ApiResponses/draftResponse.json')
            ),
            // send analytics call response
            new HttpResponse(
                200, array(), '{}'
            ),
        );
    }
}
