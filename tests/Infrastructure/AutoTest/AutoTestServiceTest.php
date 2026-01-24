<?php

namespace Logeecom\Tests\Infrastructure\AutoTest;

use Logeecom\Infrastructure\AutoTest\AutoTestLogger;
use Logeecom\Infrastructure\AutoTest\AutoTestService;
use Logeecom\Infrastructure\Http\DTO\Options;
use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use Logeecom\Infrastructure\Logger\LogData;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Infrastructure\TaskExecution\HttpTaskExecutor;
use Logeecom\Infrastructure\TaskExecution\Interfaces\QueueServiceInterface;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskExecutorInterface;
use Logeecom\Infrastructure\Utility\Events\EventBus;
use Logeecom\Tests\Infrastructure\Common\BaseInfrastructureTestWithServices;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryQueueItemRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\TestRepositoryRegistry;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestQueueService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestTaskRunnerWakeupService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestHttpClient;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\TestShopConfiguration as BusinessTestShopConfiguration;
use Packlink\BusinessLogic\Tasks\DefaultTaskMetadataProvider;
use Packlink\BusinessLogic\Tasks\Interfaces\TaskMetadataProviderInterface;

/**
 * Class AutoTestServiceTest.
 *
 * @package Logeecom\Tests\Infrastructure\AutoTest
 */
class AutoTestServiceTest extends BaseInfrastructureTestWithServices
{
    /**
     * @var TestHttpClient
     */
    protected $httpClient;

    /**
     * @before
     *
     * @throws \Exception
     */
    public function before()
    {
        parent::before();

        RepositoryRegistry::registerRepository(QueueItem::CLASS_NAME, MemoryQueueItemRepository::getClassName());

        $me = $this;
        $this->httpClient = new TestHttpClient();
        TestServiceRegister::registerService(
            HttpClient::CLASS_NAME,
            function () use ($me) {
                return $me->httpClient;
            }
        );

        $queue = new TestQueueService();
        TestServiceRegister::registerService(
            QueueServiceInterface::CLASS_NAME,
            function () use ($queue) {
                return $queue;
            }
        );

        $businessConfig = new BusinessTestShopConfiguration();
        $metadataProvider = new DefaultTaskMetadataProvider($businessConfig);
        TestServiceRegister::registerService(
            TaskMetadataProviderInterface::CLASS_NAME,
            function () use ($metadataProvider) {
                return $metadataProvider;
            }
        );

        TestServiceRegister::registerService(
            TaskExecutorInterface::CLASS_NAME,
            function () use ($queue, $metadataProvider, $businessConfig) {
                /** @var EventBus $eventBus */
                $eventBus = ServiceRegister::getService(EventBus::CLASS_NAME);

                return new HttpTaskExecutor($queue, $metadataProvider, $businessConfig, $eventBus);
            }
        );

        $wakeupService = new TestTaskRunnerWakeupService();
        TestServiceRegister::registerService(
            TaskRunnerWakeup::CLASS_NAME,
            function () use ($wakeupService) {
                return $wakeupService;
            }
        );
    }

    /**
     * @after
     * @inheritDoc
     */
    public function after()
    {
        parent::after();

        TestRepositoryRegistry::cleanUp();
        AutoTestLogger::resetInstance();
    }

    /**
     * Test setting auto-test mode.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testSetAutoTestMode()
    {
        RepositoryRegistry::registerRepository(LogData::getClassName(), MemoryRepository::getClassName());
        $taskExecutor = ServiceRegister::getService(TaskExecutorInterface::CLASS_NAME);
        $service = new AutoTestService($taskExecutor);
        $service->setAutoTestMode(true);

        $repo = RepositoryRegistry::getRepository(LogData::getClassName());
        self::assertNotNull($repo, 'Log repository should be registered.');

        $loggerService = ServiceRegister::getService(ShopLoggerAdapter::CLASS_NAME);
        self::assertNotNull($loggerService, 'Logger service should be registered.');
        self::assertInstanceOf(
            '\\Logeecom\\Infrastructure\\AutoTest\\AutoTestLogger',
            $loggerService,
            'AutoTestLogger service should be registered.'
        );

        self::assertTrue($this->shopConfig->isAutoTestMode(), 'Auto-test mode should be set.');
    }

    /**
     * Test successful start of the auto-test.
     *
     * @throws \Logeecom\Infrastructure\Exceptions\StorageNotAccessibleException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function testStartAutoTestSuccess()
    {
        RepositoryRegistry::registerRepository(LogData::getClassName(), MemoryRepository::getClassName());
        $domain = parse_url($this->shopConfig->getAsyncProcessUrl(''), PHP_URL_HOST);
        $this->shopConfig->setHttpConfigurationOptions($domain, array(new Options('test', 'value')));

        $taskExecutor = ServiceRegister::getService(TaskExecutorInterface::CLASS_NAME);
        $service = new AutoTestService($taskExecutor);
        $queueItemId = $service->startAutoTest();

        self::assertNotNull($queueItemId, 'Test task should be enqueued.');

        $status = $service->getAutoTestTaskStatus($queueItemId);
        self::assertEquals('queued', $status->taskStatus, 'AutoTest tasks should be enqueued.');
        $logger = $this->shopLogger;
        $service->stopAutoTestMode(
            function () use ($logger) {
                return $logger;
            }
        );
        // starting auto-test should produce 2 logs. Additional logs should not be added to the auto-test logs.
        Logger::logInfo('this should not be added to the log');

        $allLogs = AutoTestLogger::getInstance()->getLogs();
        $allLogsArray = AutoTestLogger::getInstance()->getLogsArray();
        self::assertNotEmpty($allLogs, 'Starting logs should be added.');
        self::assertCount(2, $allLogs, 'Additional logs should not be added.');
        self::assertCount(count($allLogs), $allLogsArray, 'ToArray should produce the same number of items.');
        self::assertEquals('Start auto-test', $allLogs[0]->getMessage(), 'Starting logs should be added.');

        $context = $allLogs[1]->getContext();
        self::assertCount(1, $context, 'Current HTTP configuration options should be logged.');
        self::assertEquals($domain, $context[0]->getName(), 'Current HTTP configuration options should be logged.');

        $options = $context[0]->getValue();
        self::assertArrayHasKey(
            'HTTPOptions',
            $options,
            'Current HTTP configuration options should be logged.'
        );

        self::assertCount(1, $options, 'One HTTP configuration options should be set.');
    }

    /**
     * Tests failure when storage is not available.
     *
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function testStartAutoTestStorageFailure()
    {
        // repository is not registered
        $taskExecutor = ServiceRegister::getService(TaskExecutorInterface::CLASS_NAME);
        $service = new AutoTestService($taskExecutor);

        $exThrown = null;
        try {
            $service->startAutoTest();
        } catch (\Logeecom\Infrastructure\Exceptions\StorageNotAccessibleException $ex) {
            $exThrown = $ex;
        }

        $this->assertNotNull($exThrown);
    }
}
