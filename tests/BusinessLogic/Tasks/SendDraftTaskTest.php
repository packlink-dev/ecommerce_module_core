<?php

namespace Logeecom\Tests\BusinessLogic\Tasks;

use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Infrastructure\TaskExecution\Task;
use Logeecom\Tests\Common\TestComponents\Order\TestOrderRepository;
use Logeecom\Tests\Common\TestComponents\TestHttpClient;
use Logeecom\Tests\Common\TestServiceRegister;
use Logeecom\Tests\Infrastructure\BaseSyncTest;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Http\DTO\ParcelInfo;
use Packlink\BusinessLogic\Http\DTO\User;
use Packlink\BusinessLogic\Http\DTO\Warehouse;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\Order\Interfaces\OrderRepository;
use Packlink\BusinessLogic\Order\OrderService;
use Packlink\BusinessLogic\Tasks\SendDraftTask;

/**
 * Class SendDraftTaskTest
 * @package Logeecom\Tests\BusinessLogic\Tasks
 * @property SendDraftTask syncTask
 */
class SendDraftTaskTest extends BaseSyncTest
{
    /**
     * @var TestHttpClient
     */
    private $httpClient;

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
        TestServiceRegister::registerService(Proxy::CLASS_NAME, function () use ($me) {
            /** @var Configuration $config */
            $config = TestServiceRegister::getService(Configuration::CLASS_NAME);

            return new Proxy($config->getAuthorizationToken(), $me->httpClient);
        });

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

        /** @var TestOrderRepository $orderRepository */
        $orderRepository = TestServiceRegister::getService(OrderRepository::CLASS_NAME);
        $order = $orderRepository->getOrder('test');

        $this->assertEquals('DE00019732CF', $order->getShipment()->getReferenceNumber());
    }

    public function testAfterFailure()
    {
        /** @var TestOrderRepository $orderRepository */
        $orderRepository = TestServiceRegister::getService(OrderRepository::CLASS_NAME);
        $orderRepository->shouldThrowException(true);
        $serialized = '';
        try {
            $this->syncTask->execute();
        } catch (\Exception $e) {
            $serialized = serialize($this->syncTask);
        }

        $this->httpClient->setMockResponses($this->getMockResponses());
        $orderRepository->shouldThrowException(false);
        /** @var SendDraftTask $task */
        $task = unserialize($serialized);
        $task->execute();

        $order = $orderRepository->getOrder('test');

        $this->assertEquals('DE00019732CF', $order->getShipment()->getReferenceNumber());
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
            new HttpResponse(
                200, array(), file_get_contents(__DIR__ . '/../../Common/ApiResponses/draftResponse.json')
            ),
        );
    }
}
