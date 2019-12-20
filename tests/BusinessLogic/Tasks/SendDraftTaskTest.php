<?php

namespace Logeecom\Tests\BusinessLogic\Tasks;

use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Infrastructure\Serializer\Concrete\NativeSerializer;
use Logeecom\Infrastructure\Serializer\Serializer;
use Logeecom\Infrastructure\TaskExecution\Task;
use Logeecom\Tests\BusinessLogic\BaseSyncTest;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Order\TestShopOrderService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\TestRepositoryRegistry;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestHttpClient;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Http\DTO\ParcelInfo;
use Packlink\BusinessLogic\Http\DTO\User;
use Packlink\BusinessLogic\Http\DTO\Warehouse;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\Order\Interfaces\ShopOrderService;
use Packlink\BusinessLogic\Order\Models\OrderShipmentDetails;
use Packlink\BusinessLogic\Order\OrderService;
use Packlink\BusinessLogic\OrderShipmentDetails\OrderShipmentDetailsService;
use Packlink\BusinessLogic\ShippingMethod\PackageTransformer;
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

        TestRepositoryRegistry::registerRepository(
            OrderShipmentDetails::getClassName(),
            MemoryRepository::getClassName()
        );

        $this->httpClient = new TestHttpClient();
        TestServiceRegister::registerService(
            HttpClient::CLASS_NAME,
            function () use ($me) {
                return $me->httpClient;
            }
        );

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

        TestServiceRegister::registerService(
            Proxy::CLASS_NAME,
            function () use ($me) {
                return new Proxy($me->shopConfig, $me->httpClient);
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

    /**
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function testExecute()
    {
        $this->httpClient->setMockResponses($this->getMockResponses());
        $this->syncTask->execute();

        /** @var OrderShipmentDetailsService $shopOrderService */
        $shopOrderService = TestServiceRegister::getService(OrderShipmentDetailsService::CLASS_NAME);
        $shipmentDetails = $shopOrderService->getDetailsByOrderId('test');

        $this->assertEquals('DE00019732CF', $shipmentDetails->getReference());
        // there should be an info message that draft is created.
        $this->assertCount(1, $this->shopLogger->loggedMessages);
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
        );
    }
}
