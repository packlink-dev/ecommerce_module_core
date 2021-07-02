<?php
/** @noinspection PhpUnhandledExceptionInspection */

namespace Logeecom\Tests\BusinessLogic\Tasks;

use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Infrastructure\Serializer\Concrete\NativeSerializer;
use Logeecom\Infrastructure\Serializer\Serializer;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Task;
use Logeecom\Tests\BusinessLogic\BaseSyncTest;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\TestWarehouse;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Order\TestShopOrderService;
use Logeecom\Tests\BusinessLogic\ShippingMethod\TestShopShippingMethodService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\TestRepositoryRegistry;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Http\DTO\ParcelInfo;
use Packlink\BusinessLogic\Http\DTO\User;
use Packlink\BusinessLogic\Order\Interfaces\ShopOrderService;
use Packlink\BusinessLogic\Order\OrderService;
use Packlink\BusinessLogic\OrderShipmentDetails\Models\OrderShipmentDetails;
use Packlink\BusinessLogic\OrderShipmentDetails\OrderShipmentDetailsService;
use Packlink\BusinessLogic\ShipmentDraft\Models\OrderSendDraftTaskMap;
use Packlink\BusinessLogic\ShipmentDraft\OrderSendDraftTaskMapService;
use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\ShippingMethod\PackageTransformer;
use Packlink\BusinessLogic\ShippingMethod\ShippingMethodService;
use Packlink\BusinessLogic\ShippingMethod\Utility\ShipmentStatus;
use Packlink\BusinessLogic\Tasks\SendDraftTask;

/**
 * Class SendDraftTaskTest.
 *
 * @package Logeecom\Tests\BusinessLogic\Tasks
 * @property SendDraftTask syncTask
 */
class SendDraftTaskTest extends BaseSyncTest
{
    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        TestRepositoryRegistry::registerRepository(
            OrderShipmentDetails::getClassName(),
            MemoryRepository::getClassName()
        );
        TestRepositoryRegistry::registerRepository(
            OrderSendDraftTaskMap::getClassName(),
            MemoryRepository::getClassName()
        );
        TestRepositoryRegistry::registerRepository(ShippingMethod::getClassName(), MemoryRepository::getClassName());

        TestServiceRegister::registerService(
            OrderShipmentDetailsService::CLASS_NAME,
            function () {
                return OrderShipmentDetailsService::getInstance();
            }
        );

        TestServiceRegister::registerService(
            OrderService::CLASS_NAME,
            function () {
                return OrderService::getInstance();
            }
        );

        TestServiceRegister::registerService(
            PackageTransformer::CLASS_NAME,
            function () {
                return PackageTransformer::getInstance();
            }
        );

        $shopOrderService = new TestShopOrderService();

        TestServiceRegister::registerService(
            ShopOrderService::CLASS_NAME,
            function () use ($shopOrderService) {
                return $shopOrderService;
            }
        );

        TestServiceRegister::registerService(
            Serializer::CLASS_NAME,
            function () {
                return new NativeSerializer();
            }
        );

        TestServiceRegister::registerService(
            OrderSendDraftTaskMapService::CLASS_NAME,
            function () {
                return OrderSendDraftTaskMapService::getInstance();
            }
        );

        TestServiceRegister::registerService(
            ShippingMethodService::CLASS_NAME,
            function () {
                return ShippingMethodService::getInstance();
            }
        );

        TestServiceRegister::registerService(
            ShopShippingMethodService::CLASS_NAME,
            function () {
                return new TestShopShippingMethodService();
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
        OrderService::resetInstance();
        ShippingMethodService::resetInstance();

        parent::tearDown();
    }

    /**
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound
     * @throws \Packlink\BusinessLogic\Http\Exceptions\DraftNotCreatedException
     * @throws \Packlink\BusinessLogic\OrderShipmentDetails\Exceptions\OrderShipmentDetailsNotFound
     */
    public function testExecute()
    {
        $this->httpClient->setMockResponses($this->getMockResponses());
        $this->syncTask->execute();

        /** @var OrderShipmentDetailsService $shopOrderService */
        $shopOrderService = TestServiceRegister::getService(OrderShipmentDetailsService::CLASS_NAME);
        $shipmentDetails = $shopOrderService->getDetailsByOrderId('test');

        $this->assertEquals('test', $shipmentDetails->getReference());
        $this->assertEquals(15.85, $shipmentDetails->getShippingCost());
        $this->assertEquals('EUR', $shipmentDetails->getCurrency());
        $this->assertEquals(ShipmentStatus::STATUS_PENDING, ShipmentStatus::getStatus($shipmentDetails->getStatus()));
        // there should be an info message that draft is created.
        $this->assertCount(2, $this->shopLogger->loggedMessages);

        /** @var OrderSendDraftTaskMapService $taskMapService */
        $taskMapService = ServiceRegister::getService(OrderSendDraftTaskMapService::CLASS_NAME);
        $taskMap = $taskMapService->getOrderTaskMap('test');
        $this->assertNotNull($taskMap, 'Order task map should be created');
        $this->assertNotEmpty($taskMap->getOrderId(), 'Order ID should be set to the order task map.');
    }

    /**
     * @expectedException \Packlink\BusinessLogic\Http\Exceptions\DraftNotCreatedException
     */
    public function testExecuteBadResponse()
    {
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), '{}')));
        $this->syncTask->execute();
    }

    /**
     * Tests idempotentness of the create draft task.
     */
    public function testDraftAlreadyCreated()
    {
        $this->httpClient->setMockResponses($this->getMockResponses());
        $this->syncTask->execute();
        // reset messages
        $this->shopLogger->loggedMessages = array();

        $this->syncTask->execute();

        $this->assertCount(1, $this->shopLogger->loggedMessages);
        $this->assertEquals(
            'Draft for order [test] has been already created. Task is terminating.',
            $this->shopLogger->loggedMessages[0]->getMessage()
        );
    }

    /**
     * Creates new instance of task that is being tested.
     *
     * @return Task
     */
    protected function createSyncTaskInstance()
    {
        return new SendDraftTask('test');
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
            // send shipment response
            new HttpResponse(
                200, array(), file_get_contents(__DIR__ . '/../Common/ApiResponses/shipment.json')
            ),
        );
    }
}
