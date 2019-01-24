<?php

namespace Logeecom\Tests\Infrastructure\TaskExecution;

use Logeecom\Infrastructure\Configuration\ConfigEntity;
use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\Logger\Interfaces\DefaultLoggerAdapter;
use Logeecom\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Infrastructure\TaskExecution\QueueItemStarter;
use Logeecom\Infrastructure\TaskExecution\QueueService;
use Logeecom\Infrastructure\Utility\Events\EventBus;
use Logeecom\Infrastructure\Utility\TimeProvider;
use Logeecom\Tests\Infrastructure\Common\TestComponents\Logger\TestDefaultLogger;
use Logeecom\Tests\Infrastructure\Common\TestComponents\Logger\TestShopLogger;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryQueueItemRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\FooTask;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestQueueService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestHttpClient;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestShopConfiguration;
use Logeecom\Tests\Infrastructure\Common\TestComponents\Utility\TestTimeProvider;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use PHPUnit\Framework\TestCase;

class QueueItemStarterTest extends TestCase
{
    /** @var \Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestQueueService */
    public $queue;
    /** @var MemoryQueueItemRepository */
    public $queueStorage;
    /** @var TestTimeProvider */
    public $timeProvider;
    /** @var \Logeecom\Tests\Infrastructure\Common\TestComponents\Logger\TestShopLogger */
    public $logger;
    /** @var Configuration */
    public $shopConfiguration;

    /**
     * @throws \Exception
     */
    public function setUp()
    {
        RepositoryRegistry::registerRepository(QueueItem::CLASS_NAME, MemoryQueueItemRepository::getClassName());
        RepositoryRegistry::registerRepository(ConfigEntity::CLASS_NAME, MemoryRepository::getClassName());

        $timeProvider = new TestTimeProvider();
        $queue = new TestQueueService();
        $shopLogger = new TestShopLogger();
        $shopConfiguration = new TestShopConfiguration();
        $shopConfiguration->setIntegrationName('Shop1');

        new TestServiceRegister(
            array(
                TimeProvider::CLASS_NAME => function () use($timeProvider) {
                    return $timeProvider;
                },
                TaskRunnerWakeup::CLASS_NAME => function () {
                    return new \Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestTaskRunnerWakeupService(
                    );
                },
                QueueService::CLASS_NAME => function () use ($queue) {
                    return $queue;
                },
                EventBus::CLASS_NAME => function () {
                    return EventBus::getInstance();
                },
                DefaultLoggerAdapter::CLASS_NAME => function() {
                    return new TestDefaultLogger();
                },
                ShopLoggerAdapter::CLASS_NAME => function() use ($shopLogger) {
                    return $shopLogger;
                },
                Configuration::CLASS_NAME => function() use ($shopConfiguration) {
                    return $shopConfiguration;
                },
                HttpClient::CLASS_NAME => function() {
                    return new TestHttpClient();
                }
            ));

        // Initialize logger component with new set of log adapters
        Logger::resetInstance();

        $this->queueStorage = RepositoryRegistry::getQueueItemRepository();
        $this->timeProvider = $timeProvider;
        $this->queue = $queue;
        $this->logger = $shopLogger;
        $this->shopConfiguration = $shopConfiguration;
    }

    /**
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function testRunningItemStarter()
    {
        // Arrange
        $queueItem = $this->queue->enqueue(
            'test',
            new \Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\FooTask()
        );
        $itemStarter = new QueueItemStarter($queueItem->getId());

        // Act
        $itemStarter->run();

        // Assert
        $findCallHistory = $this->queue->getMethodCallHistory('find');
        $startCallHistory = $this->queue->getMethodCallHistory('start');
        $finishCallHistory = $this->queue->getMethodCallHistory('finish');
        $this->assertCount(1, $findCallHistory);
        $this->assertCount(1, $startCallHistory);
        $this->assertCount(1, $finishCallHistory);
        $this->assertEquals($queueItem->getId(), $findCallHistory[0]['id']);
        /** @var QueueItem $startedQueueItem */
        $startedQueueItem = $startCallHistory[0]['queueItem'];
        $this->assertEquals($queueItem->getId(), $startedQueueItem->getId());
        /** @var QueueItem $finishedQueueItem */
        $finishedQueueItem = $finishCallHistory[0]['queueItem'];
        $this->assertEquals($queueItem->getId(), $finishedQueueItem->getId());
    }

    /**
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function testItemStarterMustBeRunnableAfterDeserialization()
    {
        // Arrange
        $queueItem = $this->queue->enqueue(
            'test',
            new \Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\FooTask()
        );
        $itemStarter = new QueueItemStarter($queueItem->getId());
        /** @var QueueItemStarter $unserializedItemStarter */
        $unserializedItemStarter = unserialize(serialize($itemStarter));

        // Act
        $unserializedItemStarter->run();

        // Assert
        $findCallHistory = $this->queue->getMethodCallHistory('find');
        $startCallHistory = $this->queue->getMethodCallHistory('start');
        $this->assertCount(1, $findCallHistory);
        $this->assertCount(1, $startCallHistory);
        $this->assertEquals($queueItem->getId(), $findCallHistory[0]['id']);
        /** @var QueueItem $startedQueueItem */
        $startedQueueItem = $startCallHistory[0]['queueItem'];
        $this->assertEquals($queueItem->getId(), $startedQueueItem->getId());
    }

    /**
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function testItemsStarterMustSetTaskExecutionContextInConfiguration()
    {
        // Arrange
        $queueItem = $this->queue->enqueue('test', new FooTask(), 'test');
        $itemStarter = new QueueItemStarter($queueItem->getId());

        // Act
        $itemStarter->run();

        // Assert
        $this->assertSame('test', $this->shopConfiguration->getContext(), 'Item starter must set task context before task execution.');
    }

    /**
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function testItemsStarterExceptionHandling()
    {
        // Arrange
        $queueItem = $this->queue->enqueue(
            'test',
            new \Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\FooTask()
        );
        $itemStarter = new QueueItemStarter($queueItem->getId());
        $this->queue->setExceptionResponse(
            'start',
            new QueueStorageUnavailableException('Simulate unavailable queue storage.')
        );

        // Act
        $itemStarter->run();
        $context = array();
        foreach ($this->logger->data->getContext() as $item) {
            $context[$item->getName()] = $item->getValue();
        }

        // Assert
        $this->assertArrayHasKey('TaskId', $context, 'Item starter must log exception messages in context.');
        $this->assertArrayHasKey('TaskId', $context, 'Item starter must log failed item id in message context.');
    }
}
