<?php

namespace Logeecom\Tests\Infrastructure\TaskExecution;

use Logeecom\Infrastructure\Configuration\ConfigEntity;
use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\Logger\Interfaces\DefaultLoggerAdapter;
use Logeecom\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\TaskExecution\Interfaces\AsyncProcessService;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerStatusStorage;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup;
use Logeecom\Infrastructure\TaskExecution\Process;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Infrastructure\TaskExecution\QueueItemStarter;
use Logeecom\Infrastructure\TaskExecution\QueueService;
use Logeecom\Infrastructure\TaskExecution\TaskRunner;
use Logeecom\Infrastructure\TaskExecution\TaskRunnerStatus;
use Logeecom\Infrastructure\Utility\Events\EventBus;
use Logeecom\Infrastructure\Utility\GuidProvider;
use Logeecom\Infrastructure\Utility\TimeProvider;
use Logeecom\Tests\Common\TestComponents\Logger\TestDefaultLogger;
use Logeecom\Tests\Common\TestComponents\Logger\TestShopLogger;
use Logeecom\Tests\Common\TestComponents\ORM\MemoryQueueItemRepository;
use Logeecom\Tests\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Common\TestComponents\ORM\MemoryStorage;
use Logeecom\Tests\Common\TestComponents\TaskExecution\FooTask;
use Logeecom\Tests\Common\TestComponents\TaskExecution\TestQueueService;
use Logeecom\Tests\Common\TestComponents\TaskExecution\TestRunnerStatusStorage;
use Logeecom\Tests\Common\TestComponents\TaskExecution\TestTaskRunnerWakeupService;
use Logeecom\Tests\Common\TestComponents\TestHttpClient;
use Logeecom\Tests\Common\TestComponents\TestShopConfiguration;
use Logeecom\Tests\Common\TestComponents\Utility\TestGuidProvider;
use Logeecom\Tests\Common\TestComponents\Utility\TestTimeProvider;
use Logeecom\Tests\Common\TestServiceRegister;
use PHPUnit\Framework\TestCase;

class TaskRunnerTest extends TestCase
{
    /** @var AsyncProcessService */
    private $asyncProcessStarter;
    /** @var TestTaskRunnerWakeupService */
    private $taskRunnerStarter;
    /** @var TestRunnerStatusStorage */
    private $runnerStatusStorage;
    /** @var TestTimeProvider */
    private $timeProvider;
    /** @var TestGuidProvider */
    private $guidProvider;
    /** @var TestShopConfiguration */
    private $configuration;
    /** @var TestShopLogger */
    private $logger;
    /** @var MemoryQueueItemRepository */
    private $queueStorage;
    /** @var TestQueueService */
    private $queue;
    /** @var TaskRunner */
    private $taskRunner;

    /**
     * @throws \Exception
     */
    protected function setUp()
    {
        RepositoryRegistry::registerRepository(QueueItem::CLASS_NAME, MemoryQueueItemRepository::getClassName());
        RepositoryRegistry::registerRepository(ConfigEntity::CLASS_NAME, MemoryRepository::getClassName());
        RepositoryRegistry::registerRepository(Process::CLASS_NAME, MemoryRepository::getClassName());

        $taskRunnerStarter = new TestTaskRunnerWakeupService();
        $runnerStatusStorage = new TestRunnerStatusStorage();
        $timeProvider = new TestTimeProvider();
        $guidProvider = TestGuidProvider::getInstance();
        $configuration = new TestShopConfiguration();
        $queue = new TestQueueService();

        $shopLogger = new TestShopLogger();

        new TestServiceRegister(
            array(
                AsyncProcessService::CLASS_NAME => function () {
                    return \Logeecom\Infrastructure\TaskExecution\AsyncProcessStarter::getInstance();
                },
                TaskRunnerWakeup::CLASS_NAME => function () use ($taskRunnerStarter) {
                    return $taskRunnerStarter;
                },
                TaskRunnerStatusStorage::CLASS_NAME => function () use ($runnerStatusStorage) {
                    return $runnerStatusStorage;
                },
                QueueService::CLASS_NAME => function () use ($queue) {
                    return $queue;
                },
                TimeProvider::CLASS_NAME => function () use ($timeProvider) {
                    return $timeProvider;
                },
                GuidProvider::CLASS_NAME => function () use ($guidProvider) {
                    return $guidProvider;
                },
                DefaultLoggerAdapter::CLASS_NAME => function () {
                    return new TestDefaultLogger();
                },
                ShopLoggerAdapter::CLASS_NAME => function () use ($shopLogger) {
                    return $shopLogger;
                },
                Configuration::CLASS_NAME => function () use ($configuration) {
                    return $configuration;
                },
                EventBus::CLASS_NAME => function () {
                    return EventBus::getInstance();
                },
                HttpClient::CLASS_NAME => function () {
                    return new TestHttpClient();
                },
            )
        );

        Logger::resetInstance();

        $this->asyncProcessStarter = \Logeecom\Infrastructure\TaskExecution\AsyncProcessStarter::getInstance();
        $this->taskRunnerStarter = $taskRunnerStarter;
        $this->runnerStatusStorage = $runnerStatusStorage;
        $this->queueStorage = RepositoryRegistry::getQueueItemRepository();
        $this->timeProvider = $timeProvider;
        $this->guidProvider = $guidProvider;
        $this->configuration = $configuration;
        $this->logger = $shopLogger;
        $this->queue = $queue;
        $this->taskRunner = new TaskRunner();

        $guid = $this->guidProvider->generateGuid();
        $currentTimestamp = $this->timeProvider->getCurrentLocalTime()->getTimestamp();
        $this->taskRunner->setGuid($guid);
        $this->runnerStatusStorage->initializeStatus(new TaskRunnerStatus($guid, $currentTimestamp));
    }

    protected function tearDown()
    {
        MemoryStorage::reset();
        \Logeecom\Infrastructure\TaskExecution\AsyncProcessStarter::resetInstance();
        parent::tearDown();
    }

    /**
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Exception
     */
    public function testRunningQueuedItems()
    {
        // Arrange
        $this->timeProvider->setCurrentLocalTime(new \DateTime('now -3 days'));
        $earliestQueue1Item = $this->queue->enqueue('queue1', new FooTask());
        $earliestQueue2Item = $this->queue->enqueue('queue2', new FooTask());

        $this->timeProvider->setCurrentLocalTime(new \DateTime('now -2 days'));
        $this->queue->generateRunningQueueItem('queue3', new FooTask());
        $this->queue->enqueue('queue1', new FooTask());
        $this->queue->enqueue('queue2', new FooTask());
        $this->queue->enqueue('queue3', new FooTask());

        // Act
        $this->taskRunner->run();

        // Assert
        $startCallHistory = RepositoryRegistry::getRepository(Process::CLASS_NAME)->select();
        $this->assertCount(2, $startCallHistory, 'Run call should start earliest queued items asynchronously.');
        $this->assertTrue(
            $this->isQueueItemInStartCallHistory($earliestQueue1Item, $startCallHistory),
            'Run call should start only earliest queued items asynchronously.'
        );
        $this->assertTrue(
            $this->isQueueItemInStartCallHistory($earliestQueue2Item, $startCallHistory),
            'Run call should start only earliest queued items asynchronously.'
        );
    }

    /**
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Exception
     */
    public function testMaximumConcurrentExecutionLimit()
    {
        // Arrange
        $this->configuration->setMaxStartedTasksLimit(6);

        $this->timeProvider->setCurrentLocalTime(new \DateTime('now -2 days'));
        $earliestQueue5Item = $this->queue->enqueue('queue5', new FooTask());
        $this->timeProvider->setCurrentLocalTime(new \DateTime('now -3 days'));
        $earliestQueue4Item = $this->queue->enqueue('queue4', new FooTask());
        $this->timeProvider->setCurrentLocalTime(new \DateTime('now -4 days'));
        $earliestQueue3Item = $this->queue->enqueue('queue3', new FooTask());
        $this->timeProvider->setCurrentLocalTime(new \DateTime('now -5 days'));
        $earliestQueue2Item = $this->queue->enqueue('queue2', new FooTask());
        $this->timeProvider->setCurrentLocalTime(new \DateTime('now -6 days'));
        $earliestQueue1Item = $this->queue->enqueue('queue1', new FooTask());

        $this->timeProvider->setCurrentLocalTime(new \DateTime('now -7 days'));
        $this->queue->generateRunningQueueItem('runningQueue1', new FooTask());
        $this->queue->generateRunningQueueItem('runningQueue2', new FooTask());
        $this->queue->generateRunningQueueItem('runningQueue3', new FooTask());

        // Act
        $this->taskRunner->run();

        // Assert
        $startCallHistory = RepositoryRegistry::getRepository(Process::CLASS_NAME)->select();
        $this->assertCount(
            3,
            $startCallHistory,
            'Run call should start only up to max allowed running tasks where already running tasks must be considered.'
        );
        $this->assertTrue(
            $this->isQueueItemInStartCallHistory($earliestQueue1Item, $startCallHistory),
            'Run call should start only earliest queued items asynchronously.'
        );
        $this->assertTrue(
            $this->isQueueItemInStartCallHistory($earliestQueue2Item, $startCallHistory),
            'Run call should start only earliest queued items asynchronously.'
        );
        $this->assertTrue(
            $this->isQueueItemInStartCallHistory($earliestQueue3Item, $startCallHistory),
            'Run call should start only earliest queued items asynchronously.'
        );
        $this->assertFalse(
            $this->isQueueItemInStartCallHistory($earliestQueue4Item, $startCallHistory),
            'Run call should start only earliest queued items asynchronously.'
        );
        $this->assertFalse(
            $this->isQueueItemInStartCallHistory($earliestQueue5Item, $startCallHistory),
            'Run call should start only earliest queued items asynchronously.'
        );
    }

    /**
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Exception
     */
    public function testRequeueProgressedButExpiredTask()
    {
        // Arrange
        $this->timeProvider->setCurrentLocalTime(new \DateTime('now -7 days'));
        $progress = 31;
        $lastExecutionProgress = 30;
        $expiredRunningItem = $this->queue->generateRunningQueueItem(
            'runningQueue1',
            new FooTask(),
            $progress,
            $lastExecutionProgress
        );
        $this->timeProvider->setCurrentLocalTime(new \DateTime());

        // Act
        $this->taskRunner->run();

        // Assert
        $requeueCallHistory = $this->queue->getMethodCallHistory('requeue');
        $this->assertCount(
            1,
            $requeueCallHistory,
            'Run call should requeue expired tasks if it progressed since last execution.'
        );
        /** @var QueueItem $actualItem */
        $actualItem = $requeueCallHistory[0]['queueItem'];
        $this->assertEquals($expiredRunningItem->getId(), $actualItem->getId());
    }

    /**
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Exception
     */
    public function testFailingExpiredRunningTasks()
    {
        // Arrange
        $this->timeProvider->setCurrentLocalTime(new \DateTime('now -7 days'));
        $expiredRunningItem = $this->queue->generateRunningQueueItem('runningQueue1', new FooTask(), 5269, 5269);
        $this->timeProvider->setCurrentLocalTime(new \DateTime());

        // Act
        $this->taskRunner->run();

        // Assert
        $failCallHistory = $this->queue->getMethodCallHistory('fail');
        $this->assertCount(1, $failCallHistory, 'Run call should fail expired tasks.');

        /** @var QueueItem $actualItem */
        $actualItem = $failCallHistory[0]['queueItem'];
        /** @var FooTask $actualTestTask */
        $actualTestTask = $actualItem->getTask();
        $actualFailureDescription = $failCallHistory[0]['failureDescription'];
        $this->assertEquals($expiredRunningItem->getId(), $actualItem->getId());
        $this->assertSame(
            1,
            $actualTestTask->getMethodCallCount('reconfigure'),
            'Run call should reconfigure failing expired tasks.'
        );
        $this->assertContains((string)$expiredRunningItem->getId(), $actualFailureDescription);
        $this->assertContains($expiredRunningItem->getTaskType(), $actualFailureDescription);
        $this->assertContains('failed due to extended inactivity period', $actualFailureDescription);
    }

    public function testRunnerShouldBeInactiveAtTheEndOfLifecycle()
    {
        // Arrange
        $guid = 'test';
        $this->taskRunner->setGuid($guid);

        // Act
        $this->taskRunner->run();

        // Assert
        $setStatusCallHistory = $this->runnerStatusStorage->getMethodCallHistory('setStatus');
        $this->assertCount(
            1,
            $setStatusCallHistory,
            'Run call must set current runner as inactive at the end of lifecycle.'
        );

        /** @var TaskRunnerStatus $runnerStatus */
        $runnerStatus = $setStatusCallHistory[0]['status'];
        $this->assertEquals(
            TaskRunnerStatus::createNullStatus(),
            $runnerStatus,
            'Run call must set current runner as inactive at the end of lifecycle.'
        );
    }

    /**
     * @throws \Exception
     */
    public function testAutoWakeup()
    {
        // Arrange
        $startTime = new \DateTime();
        $this->timeProvider->setCurrentLocalTime($startTime);

        // Act
        $this->taskRunner->run();

        // Assert
        $wakeupCallHistory = $this->taskRunnerStarter->getMethodCallHistory('wakeup');
        $this->assertCount(1, $wakeupCallHistory, 'Run call must auto wakeup at the end of lifecycle.');

        $expectedTimestamp = $startTime->getTimestamp() + TaskRunner::WAKEUP_DELAY;
        $actualTimestamp = $this->timeProvider->getCurrentLocalTime()->getTimestamp();
        $this->assertGreaterThanOrEqual($expectedTimestamp, $actualTimestamp, 'Wakeup call must be delayed.');
    }

    /**
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Exception
     */
    public function testRunWhenRunnerExpired()
    {
        // Arrange
        $this->timeProvider->setCurrentLocalTime(new \DateTime('now -7 days'));
        $this->queue->generateRunningQueueItem('runningQueue1', new FooTask());

        $this->timeProvider->setCurrentLocalTime(new \DateTime('now -2 days'));
        $currentTimestamp = $this->timeProvider->getCurrentLocalTime()->getTimestamp();
        $this->runnerStatusStorage->initializeStatus(new TaskRunnerStatus('test', $currentTimestamp));
        $this->taskRunner->setGuid('test');

        $this->timeProvider->setCurrentLocalTime(new \DateTime());
        $this->queue->enqueue('queue', new FooTask());

        $this->taskRunnerStarter->resetCallHistory();

        // Act
        $this->taskRunner->run();

        // Assert
        $startCallHistory = RepositoryRegistry::getRepository(Process::CLASS_NAME)->select();
        $failCallHistory = $this->queue->getMethodCallHistory('fail');
        $wakeupCallHistory = $this->taskRunnerStarter->getMethodCallHistory('wakeup');
        $this->assertCount(1, $wakeupCallHistory, 'Run call must auto wakeup if no active runner is detected.');
        $this->assertCount(0, $startCallHistory, 'Run call when there is no live runner must not start any task.');
        $this->assertCount(0, $failCallHistory, 'Run call when there is no live runner must not fail any task.');
        $this->assertTrue(
            $this->logger->isMessageContainedInLog('Task runner started but it is expired.'),
            'Task runner must log messages when it detects expiration.'
        );
    }

    /**
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Exception
     */
    public function testRunWhenRunnerGuidIsNotSetAsLive()
    {
        // Arrange
        $this->timeProvider->setCurrentLocalTime(new \DateTime('now -7 days'));
        $this->queue->generateRunningQueueItem('runningQueue1', new FooTask());

        $this->timeProvider->setCurrentLocalTime(new \DateTime());
        $this->queue->enqueue('queue', new FooTask());

        $currentTimestamp = $this->timeProvider->getCurrentLocalTime()->getTimestamp();
        $this->runnerStatusStorage->initializeStatus(new TaskRunnerStatus('test', $currentTimestamp));
        $this->taskRunner->setGuid('different_guid');

        $this->taskRunnerStarter->resetCallHistory();

        // Act
        $this->taskRunner->run();

        // Assert
        $startCallHistory = RepositoryRegistry::getRepository(Process::CLASS_NAME)->select();
        $failCallHistory = $this->queue->getMethodCallHistory('fail');
        $wakeupCallHistory = $this->taskRunnerStarter->getMethodCallHistory('wakeup');
        $this->assertCount(1, $wakeupCallHistory, 'Run call must auto wakeup if no active runner is detected.');
        $this->assertCount(0, $startCallHistory, 'Run call when there is no live runner must not start any task.');
        $this->assertCount(0, $failCallHistory, 'Run call when there is no live runner must not fail any task.');
        $this->assertTrue(
            $this->logger->isMessageContainedInLog('Task runner started but it is not active anymore.'),
            'Task runner must log messages when it detects expiration.'
        );
    }

    private function isQueueItemInStartCallHistory(QueueItem $needle, array $callHistory)
    {
        /** @var QueueItem $queueItem */
        /** @var Process $callHistoryItem */
        foreach ($callHistory as $callHistoryItem) {
            /** @var QueueItemStarter $queueItemStarter */
            $queueItemStarter = $callHistoryItem->getRunner();
            if ($queueItemStarter->getQueueItemId() === $needle->getId()) {
                return true;
            }
        }

        return false;
    }
}
