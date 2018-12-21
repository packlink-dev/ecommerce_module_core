<?php

namespace Logeecom\Tests\Infrastructure\TaskExecution;

use Logeecom\Tests\Common\TestServiceRegister;
use PHPUnit\Framework\TestCase;
use Logeecom\Infrastructure\Interfaces\DefaultLoggerAdapter;
use Logeecom\Infrastructure\Interfaces\Exposed\TaskRunnerWakeup;
use Logeecom\Infrastructure\Configuration;
use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\Interfaces\Required\ShopLoggerAdapter;
use Logeecom\Infrastructure\Interfaces\Required\TaskQueueStorage;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use Logeecom\Infrastructure\TaskExecution\Queue;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Infrastructure\TaskExecution\QueueItemStarter;
use Logeecom\Infrastructure\Utility\TimeProvider;
use Logeecom\Tests\Common\TestComponents\Logger\TestDefaultLogger;
use Logeecom\Tests\Common\TestComponents\TestShopConfiguration;
use Logeecom\Tests\Common\TestComponents\Logger\TestShopLogger;
use Logeecom\Tests\Common\TestComponents\TaskExecution\FooTask;
use Logeecom\Tests\Common\TestComponents\TaskExecution\InMemoryTestQueueStorage;
use Logeecom\Tests\Common\TestComponents\TaskExecution\TestQueue;
use Logeecom\Tests\Common\TestComponents\TaskExecution\TestTaskRunnerWakeup;
use Logeecom\Tests\Common\TestComponents\TestHttpClient;
use Logeecom\Tests\Common\TestComponents\Utility\TestTimeProvider;

class QueueItemStarterTest extends TestCase
{
    /** @var TestQueue */
    private $queue;
    /** @var InMemoryTestQueueStorage */
    private $queueStorage;
    /** @var TestTimeProvider */
    private $timeProvider;
    /** @var TestShopLogger */
    private $logger;
    /** @var Configuration */
    private $shopConfiguration;

    /**
     * @throws \Exception
     */
    public function setUp()
    {
        $queueStorage = new InMemoryTestQueueStorage();
        $timeProvider = new TestTimeProvider();
        $queue = new TestQueue();
        $shopLogger = new TestShopLogger();
        $shopConfiguration = new TestShopConfiguration();
        $shopConfiguration->setIntegrationName('Shop1');

        new TestServiceRegister(
            array(
                TaskQueueStorage::CLASS_NAME => function () use($queueStorage) {
                    return $queueStorage;
                },
                TimeProvider::CLASS_NAME => function () use($timeProvider) {
                    return $timeProvider;
                },
                TaskRunnerWakeup::CLASS_NAME => function () {
                    return new TestTaskRunnerWakeup();
                },
                Queue::CLASS_NAME => function () use($queue) {
                    return $queue;
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
        new Logger();

        $this->queueStorage = $queueStorage;
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
        $queueItem = $this->queue->enqueue('test', new FooTask());
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
        $queueItem = $this->queue->enqueue('test', new FooTask());
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
    public function testItemsStarterMustSetTaskExecutionContextInConfiguraion()
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
        $queueItem = $this->queue->enqueue('test', new FooTask());
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
