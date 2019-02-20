<?php

namespace Logeecom\Tests\BusinessLogic\Tasks;

use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Infrastructure\TaskExecution\Task;
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
use Packlink\BusinessLogic\Tasks\UpdateShipmentDataTask;

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

                return new Proxy($config->getAuthorizationToken(), $me->httpClient);
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

        /** @var \Logeecom\Tests\BusinessLogic\Common\TestComponents\Order\TestOrderRepository $orderRepository */
        $orderRepository = TestServiceRegister::getService(OrderRepository::CLASS_NAME);
        $order = $orderRepository->getOrder('test');

        $this->assertEquals(15.85, $order->getBasePrice());
    }

    public function testAfterFailure()
    {
        /** @var \Logeecom\Tests\BusinessLogic\Common\TestComponents\Order\TestOrderRepository $orderRepository */
        $orderRepository = TestServiceRegister::getService(OrderRepository::CLASS_NAME);
        $orderRepository->shouldThrowException(true);
        $serialized = '';
        try {
            $this->syncTask->execute();
        } catch (\Exception $e) {
            $serialized = serialize($this->syncTask);
        }

        $this->httpClient->setMockResponses($this->getMockResponses());
        $orderRepository->shouldThrowException();
        /** @var UpdateShipmentDataTask $task */
        $task = unserialize($serialized);
        $task->execute();

        $order = $orderRepository->getOrder('test');

        $this->assertEquals(15.85, $order->getBasePrice());
    }

    /**
     * Creates new instance of task that is being tested.
     *
     * @return Task
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
                200, array(), file_get_contents(__DIR__ . '/../Common/ApiResponses/shipmentLabels.json')
            ),
            new HttpResponse(
                200, array(), file_get_contents(__DIR__ . '/../Common/ApiResponses/tracking.json')
            ),
        );
    }
}