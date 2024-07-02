<?php /** @noinspection PhpMissingDocCommentInspection */

namespace Logeecom\Tests\BusinessLogic\Controllers;

use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\ORM\QueryFilter\Operators;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Infrastructure\TaskExecution\QueueService;
use Logeecom\Infrastructure\TaskExecution\TaskRunnerWakeupService;
use Logeecom\Tests\Infrastructure\Common\BaseInfrastructureTestWithServices;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryQueueItemRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\TestRepositoryRegistry;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestQueueService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestTaskRunnerWakeupService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestCurlHttpClient;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestHttpClient;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Controllers\AutoConfigurationController;
use Packlink\BusinessLogic\Controllers\UpdateShippingServicesTaskStatusController;

/**
 * Class AutoConfigurationControllerTest.
 *
 * @package Logeecom\Tests\BusinessLogic\Controllers
 */
class AutoConfigurationControllerTest extends BaseInfrastructureTestWithServices
{
    /**
     * @var TestHttpClient
     */
    public $httpClient;

    /**
     * @before
     * @inheritdoc
     */
    public function before()
    {
        parent::before();

        RepositoryRegistry::registerRepository(QueueItem::CLASS_NAME, MemoryQueueItemRepository::getClassName());

        $me = $this;
        $this->httpClient = new TestCurlHttpClient();
        TestServiceRegister::registerService(
            HttpClient::CLASS_NAME,
            function () use ($me) {
                return $me->httpClient;
            }
        );

        $queue = new TestQueueService();
        TestServiceRegister::registerService(
            QueueService::CLASS_NAME,
            function () use ($queue) {
                return $queue;
            }
        );

        $wakeupService = new TestTaskRunnerWakeupService();
        TestServiceRegister::registerService(
            TaskRunnerWakeupService::CLASS_NAME,
            function () use ($wakeupService) {
                return $wakeupService;
            }
        );

        $this->shopConfig->setAutoConfigurationUrl('http://example.com');
    }

    /**
     * @after
     * @inheritDoc
     */
    public function after()
    {
        parent::after();

        TestRepositoryRegistry::cleanUp();
    }

    /**
     * Test auto-configure to be successful with default options
     */
    public function testAutoConfigureSuccessfullyWithDefaultOptions()
    {
        $response = $this->getResponse(200);
        $this->httpClient->setMockResponses(array($response));

        $controller = new AutoConfigurationController();
        $success = $controller->start();

        $this->assertTrue($success, 'Auto-configure must be successful if default configuration request passed.');
    }

    /**
     * Test auto-configure to be successful with default options.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    public function testAutoConfigureSuccessfullyWithEnqueuedTask()
    {
        $response = $this->getResponse(200);
        $this->httpClient->setMockResponses(array($response));

        $controller = new AutoConfigurationController();
        $controller->start(true);

        $taskController = new UpdateShippingServicesTaskStatusController();
        $status = $taskController->getLastTaskStatus();
        $this->assertNotEquals(QueueItem::FAILED, $status);
    }

    /**
     * Test auto-configure to be started, but task expired.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    public function testAutoConfigureEnqueuedTaskExpired()
    {
        $response = $this->getResponse(200);
        $this->httpClient->setMockResponses(array($response));

        $controller = new AutoConfigurationController();
        $controller->start(true);

        $this->timeProvider->setCurrentLocalTime(new \DateTime('now +10 minutes'));
        $taskController = new UpdateShippingServicesTaskStatusController();
        $status = $taskController->getLastTaskStatus();

        $this->assertEquals(QueueItem::FAILED, $status);
    }

    /**
     * Test auto-configure to be started, but task failed.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    public function testAutoConfigureEnqueuedTaskFailed()
    {
        $status = $this->startAutoConfigureAndSetTaskStatus(QueueItem::FAILED);
        $this->assertEquals(QueueItem::FAILED, $status);
    }

    /**
     * Test auto-configure to be started and task completed.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    public function testAutoConfigureEnqueuedTaskCompleted()
    {
        $status = $this->startAutoConfigureAndSetTaskStatus(QueueItem::COMPLETED);
        $this->assertEquals(QueueItem::COMPLETED, $status);
    }

    /**
     * Test auto-configure to fail to start.
     */
    public function testAutoConfigureFailed()
    {
        $response = $this->getResponse(400);
        $this->httpClient->setMockResponses(array($response));

        $controller = new AutoConfigurationController();
        $success = $controller->start();

        $this->assertFalse($success);
    }

    /**
     * @param string $taskStatus
     *
     * @return string
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    private function startAutoConfigureAndSetTaskStatus($taskStatus)
    {
        $response = $this->getResponse(200);
        $this->httpClient->setMockResponses(array($response));

        $controller = new AutoConfigurationController();
        $controller->start(true);
        $repo = RepositoryRegistry::getQueueItemRepository();
        $filter = new QueryFilter();
        $filter->where('taskType', Operators::EQUALS, 'UpdateShippingServicesTask');
        $filter->where('status', Operators::EQUALS, QueueItem::QUEUED);
        $queueItem = $repo->selectOne($filter);
        $queueItem->setStatus($taskStatus);
        $repo->update($queueItem);

        $taskController = new UpdateShippingServicesTaskStatusController();

        return $taskController->getLastTaskStatus();
    }

    private function getResponse($code)
    {
        // \r is added because HTTP response string from curl has CRLF line separator
        return array(
            'status' => $code,
            'data' => "HTTP/1.1 100 Continue\r
\r
HTTP/1.1 $code OK\r
Cache-Control: no-cache\r
Server: test\r
Date: Wed Jul 4 15:32:03 2019\r
Connection: Keep-Alive:\r
Content-Type: application/json\r
Content-Length: 24860\r
X-Custom-Header: Content: database\r
\r
{\"status\":\"success\"}",
        );
    }
}
