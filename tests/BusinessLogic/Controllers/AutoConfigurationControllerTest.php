<?php /** @noinspection PhpMissingDocCommentInspection */

namespace Logeecom\Tests\BusinessLogic\Controllers;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\ORM\QueryFilter\Operators;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\AsyncProcessUrlProviderInterface;
use Logeecom\Infrastructure\TaskExecution\HttpTaskExecutor;
use Logeecom\Infrastructure\TaskExecution\Interfaces\QueueServiceInterface;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerConfigInterface;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskStatusProviderInterface;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Infrastructure\TaskExecution\QueueService;
use Logeecom\Infrastructure\TaskExecution\QueueTaskStatusProvider;
use Logeecom\Infrastructure\TaskExecution\TaskRunnerConfig;
use Logeecom\Infrastructure\TaskExecution\TaskRunnerWakeupService;
use Logeecom\Infrastructure\Utility\Events\EventBus;
use Logeecom\Infrastructure\Utility\TimeProvider;
use Logeecom\Tests\Infrastructure\Common\BaseInfrastructureTestWithServices;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\TestShopConfiguration as BusinessTestShopConfiguration;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Scheduler\TestScheduler;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryQueueItemRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\TestRepositoryRegistry;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestAsyncProcessUrlProvider;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestQueueService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestTaskRunnerWakeupService;
use Packlink\BusinessLogic\Scheduler\Interfaces\SchedulerInterface;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestCurlHttpClient;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestHttpClient;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Controllers\AutoConfigurationController;
use Packlink\BusinessLogic\Controllers\UpdateShippingServicesTaskStatusController;
use Packlink\BusinessLogic\UpdateShippingServices\Interfaces\UpdateShippingServiceTaskStatusServiceInterface;
use Packlink\BusinessLogic\UpdateShippingServices\Models\UpdateShippingServiceTaskStatus;
use Packlink\BusinessLogic\UpdateShippingServices\UpdateShippingServiceTaskStatusService;
use Packlink\BusinessLogic\UpdateShippingServices\UpdateShippingServicesOrchestrator;

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
     * @var TestQueueService
     */
    private $queueService;

    /**@var UpdateShippingServiceTaskStatusServiceInterface $updateShippingServiceTaskStatusService */
    private $updateShippingServiceTaskStatusService;

    /**
     * @before
     * @inheritdoc
     */
    public function before()
    {
        parent::before();

        RepositoryRegistry::registerRepository(QueueItem::CLASS_NAME, MemoryQueueItemRepository::getClassName());


        TestServiceRegister::registerService(
            AsyncProcessUrlProviderInterface::CLASS_NAME,
            function () {
                return new TestAsyncProcessUrlProvider();
            }
        );

        TestServiceRegister::registerService(
            TaskRunnerConfigInterface::CLASS_NAME,
            function () {
                $config = ServiceRegister::getService(\Logeecom\Infrastructure\Configuration\Configuration::CLASS_NAME);
                $urlProvider = ServiceRegister::getService(AsyncProcessUrlProviderInterface::CLASS_NAME);

                return new TaskRunnerConfig($config, $urlProvider);
            }
        );

       TestServiceRegister::registerService(TaskStatusProviderInterface::CLASS_NAME, function ()
       {
           return new QueueTaskStatusProvider(ServiceRegister::getService(QueueServiceInterface::CLASS_NAME),
               $this->timeProvider);
       });


        $me = $this;
        $this->httpClient = new TestCurlHttpClient();
        TestServiceRegister::registerService(
            HttpClient::CLASS_NAME,
            function () use ($me) {
                return $me->httpClient;
            }
        );

        $queue = new TestQueueService();
        $this->queueService = $queue;
        TestServiceRegister::registerService(
            QueueService::CLASS_NAME,
            function () use ($queue) {
                return $queue;
            }
        );

        RepositoryRegistry::registerRepository(
            UpdateShippingServiceTaskStatus::CLASS_NAME,
            MemoryRepository::getClassName()
        );

        TestServiceRegister::registerService(
            UpdateShippingServiceTaskStatusServiceInterface::class,
            function () {
                $repo = RepositoryRegistry::getRepository(UpdateShippingServiceTaskStatus::CLASS_NAME);
                return new UpdateShippingServiceTaskStatusService($repo);
            }
        );

        $this->updateShippingServiceTaskStatusService = ServiceRegister::getService(
            UpdateShippingServiceTaskStatusServiceInterface::class);


        $scheduler = new TestScheduler();
        TestServiceRegister::registerService(
            SchedulerInterface::class,
            function () use ($scheduler) {
                return $scheduler;
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

        $controller = new AutoConfigurationController($this->createOrchestrator(), $this->updateShippingServiceTaskStatusService);
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

        $controller = new AutoConfigurationController($this->createOrchestrator(), $this->updateShippingServiceTaskStatusService);
        $controller->start(true);

        $statusService = ServiceRegister::getService(UpdateShippingServiceTaskStatusServiceInterface::class);

        $taskController = new UpdateShippingServicesTaskStatusController($this->updateShippingServiceTaskStatusService);;
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

        $controller = new AutoConfigurationController($this->createOrchestrator(), $this->updateShippingServiceTaskStatusService);
        $controller->start(true);

        $this->timeProvider->setCurrentLocalTime(new \DateTime('now +10 minutes'));
        $taskController = new UpdateShippingServicesTaskStatusController($this->updateShippingServiceTaskStatusService);
        $status = $taskController->getLastTaskStatus();

        $this->assertEquals(QueueItem::CREATED, $status);
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

        $controller = new AutoConfigurationController($this->createOrchestrator(), $this->updateShippingServiceTaskStatusService);
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

        $controller = new AutoConfigurationController($this->createOrchestrator(), $this->updateShippingServiceTaskStatusService);
        $controller->start(true);
        /** @var Configuration $configuration */
        $configuration = ServiceRegister::getService(\Logeecom\Infrastructure\Configuration\Configuration::CLASS_NAME);

        $this->updateShippingServiceTaskStatusService->upsertStatus(
            $configuration->getContext(),
            $taskStatus
        );

        $taskController = new UpdateShippingServicesTaskStatusController($this->updateShippingServiceTaskStatusService);

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

    /**
     * @return HttpTaskExecutor
     */
    private function createTaskExecutor()
    {
        $taskConfig = new BusinessTestShopConfiguration();

        $taskRunnerConfig = ServiceRegister::getService(TaskRunnerConfigInterface::CLASS_NAME);
        $metadataProvider = new TaskMetadataProviderTest(
            $taskRunnerConfig->getDefaultQueueName(),
            $taskConfig->getContext()
        );

        return new HttpTaskExecutor(
            $this->queueService,
            $metadataProvider,
            $taskConfig,
            EventBus::getInstance(),
            ServiceRegister::getService(TimeProvider::CLASS_NAME),
            ServiceRegister::getService(SchedulerInterface::class),
            $taskRunnerConfig
        );
    }

    private function createOrchestrator()
    {
        return new UpdateShippingServicesOrchestrator(
            $this->createTaskExecutor(),
            $this->updateShippingServiceTaskStatusService
        );
    }
}
