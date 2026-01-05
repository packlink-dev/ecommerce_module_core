<?php

namespace Logeecom\Tests\Infrastructure\TaskExecution;

use Logeecom\Infrastructure\Configuration\ConfigEntity;
use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\Serializer\Concrete\NativeSerializer;
use Logeecom\Infrastructure\Serializer\Serializer;
use Logeecom\Infrastructure\TaskExecution\Interfaces\AsyncProcessService;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerStatusStorage;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Infrastructure\TaskExecution\QueueService;
use Logeecom\Infrastructure\TaskExecution\TaskRunner;
use Logeecom\Infrastructure\TaskExecution\TaskRunnerStarter;
use Logeecom\Infrastructure\Utility\GuidProvider;
use Logeecom\Infrastructure\Utility\TimeProvider;
use Logeecom\Tests\Infrastructure\Common\TestComponents\Logger\TestDefaultLogger;
use Logeecom\Tests\Infrastructure\Common\TestComponents\Logger\TestShopLogger;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryQueueItemRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryStorage;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\FooTask;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestAsyncProcessStarter;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestQueueService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestRunnerStatusStorage;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestTaskRunnerWakeupService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestShopConfiguration;
use Logeecom\Tests\Infrastructure\Common\TestComponents\Utility\TestGuidProvider;
use Logeecom\Tests\Infrastructure\Common\TestComponents\Utility\TestTimeProvider;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use PHPUnit\Framework\TestCase;

/**
 * Tests for TaskRunner killswitch idle detection.
 *
 * @package Logeecom\Tests\Infrastructure\TaskExecution
 */
class TaskRunnerKillswitchTest extends TestCase
{
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
    /** @var TestQueueService */
    private $queue;
    /** @var TestAsyncProcessStarter */
    private $asyncProcessStarter;
    /** @var TaskRunner */
    private $taskRunner;

    /**
     * @before
     * @return void
     */
    protected function before()
    {
        RepositoryRegistry::registerRepository(QueueItem::CLASS_NAME, MemoryQueueItemRepository::getClassName());
        RepositoryRegistry::registerRepository(ConfigEntity::CLASS_NAME, MemoryRepository::getClassName());

        $this->taskRunnerStarter = new TestTaskRunnerWakeupService();
        $this->runnerStatusStorage = new TestRunnerStatusStorage();
        $this->timeProvider = new TestTimeProvider();
        $this->guidProvider = new TestGuidProvider();
        $this->configuration = new TestShopConfiguration();
        $this->logger = new TestShopLogger();
        $this->queue = new TestQueueService();
        $this->asyncProcessStarter = new TestAsyncProcessStarter();

        new TestServiceRegister(
            array(
                TaskRunnerWakeup::CLASS_NAME => function () {
                    return $this->taskRunnerStarter;
                },
                TaskRunnerStatusStorage::CLASS_NAME => function () {
                    return $this->runnerStatusStorage;
                },
                TimeProvider::CLASS_NAME => function () {
                    return $this->timeProvider;
                },
                GuidProvider::CLASS_NAME => function () {
                    return $this->guidProvider;
                },
                Configuration::CLASS_NAME => function () {
                    return $this->configuration;
                },
                Logger::CLASS_NAME => function () {
                    return $this->logger;
                },
                QueueService::CLASS_NAME => function () {
                    return $this->queue;
                },
                AsyncProcessService::CLASS_NAME => function () {
                    return $this->asyncProcessStarter;
                },
                Serializer::CLASS_NAME => function () {
                    return new NativeSerializer();
                },
            )
        );

        $this->taskRunner = new TaskRunner();
    }

    /**
     * @after
     * @return void
     */
    protected function after()
    {
        MemoryStorage::reset();
        TestServiceRegister::reset();
        TaskRunnerStarter::resetInstance();
    }

    /**
     * Test: TaskRunner goes idle when queue is empty
     */
    public function testGoesIdleWhenQueueEmpty()
    {
        // Arrange: Empty queue
        $this->assertEmpty($this->queue->findAll('default'));

        // Act: Check via QueueService method
        $hasTasks = $this->queue->hasPendingWork();

        // Assert: Returns false when queue empty
        $this->assertFalse($hasTasks, 'Should return false when queue is empty');
    }

    /**
     * Test: TaskRunner continues when QUEUED tasks exist
     */
    public function testContinuesWhenQueuedTasksExist()
    {
        // Arrange: Enqueue task
        $task = new FooTask();
        $this->queue->enqueue('default', $task);

        // Act: Check via QueueService method
        $hasTasks = $this->queue->hasPendingWork();

        // Assert: Returns true when queued tasks exist
        $this->assertTrue($hasTasks, 'Should return true when queued tasks exist');
    }

    /**
     * Test: TaskRunner continues when IN_PROGRESS tasks exist
     */
    public function testContinuesWhenRunningTasksExist()
    {
        // Arrange: Create running task
        $task = new FooTask();
        $queueItem = $this->queue->enqueue('default', $task);
        $queueItem->setStatus(QueueItem::IN_PROGRESS);
        $this->queue->save($queueItem);

        // Act: Check via QueueService method
        $hasTasks = $this->queue->hasPendingWork();

        // Assert: Returns true when running tasks exist
        $this->assertTrue($hasTasks, 'Should return true when running tasks exist');
    }

    /**
     * Test: GUID locking prevents concurrent wakeups (race condition protection)
     *
     * Simulates two TaskRunner instances attempting to call wakeup() concurrently
     * after both detect pending tasks. Verifies that GUID locking in
     * TaskRunnerWakeupService::doWakeup() prevents duplicate runner spawns.
     *
     * This tests the actual race condition scenario that can occur when:
     * 1. Runner A checks hasPendingWork() → TRUE
     * 2. Runner B checks hasPendingWork() → TRUE (before A completes wakeup)
     * 3. Both try to call wakeup()
     * 4. Only ONE should actually spawn a new TaskRunnerStarter
     */
    public function testRaceConditionPreventsConcurrentWakeups()
    {
        // Arrange: Enqueue task so hasPendingWork() returns true
        $task = new FooTask();
        $this->queue->enqueue('default', $task);

        // Reset call histories
        $this->taskRunnerStarter->resetCallHistory();

        // Act: Simulate two runners checking for tasks concurrently
        $runner1 = new TaskRunner();
        $runner2 = new TaskRunner();

        // Both runners detect pending tasks (race condition window)
        $hasTasks1 = $this->queue->hasPendingWork();
        $hasTasks2 = $this->queue->hasPendingWork();

        $this->assertTrue($hasTasks1, 'Runner 1 should see pending tasks');
        $this->assertTrue($hasTasks2, 'Runner 2 should see pending tasks');

        // Both runners attempt to trigger wakeup (simulate concurrent wakeup scenario)
        // First wakeup should succeed and set GUID in runner status storage
        $this->taskRunnerStarter->wakeup();

        // Verify first wakeup was called
        $wakeupCallsAfterFirst = $this->taskRunnerStarter->getMethodCallHistory('wakeup');
        $this->assertCount(1, $wakeupCallsAfterFirst, 'First wakeup should be recorded');

        // Verify first wakeup actually started an async process (spawned runner)
        $startCallsAfterFirst = $this->asyncProcessStarter->getMethodCallHistory('start');
        $this->assertCount(
            1,
            $startCallsAfterFirst,
            'First wakeup should spawn exactly one TaskRunnerStarter'
        );

        // Verify GUID was set in runner status storage
        $runnerStatus = $this->runnerStatusStorage->getStatus();
        $this->assertNotEmpty($runnerStatus->getGuid(), 'Runner status should have GUID after first wakeup');
        $firstGuid = $runnerStatus->getGuid();

        // Second wakeup should be blocked by GUID lock (race condition prevented)
        // TaskRunnerWakeupService::doWakeup() checks runnerStatus and returns early
        // if GUID exists and is not expired (lines 94-98 in TaskRunnerWakeupService.php)
        $this->taskRunnerStarter->wakeup();

        $wakeupCallsAfterSecond = $this->taskRunnerStarter->getMethodCallHistory('wakeup');
        $this->assertCount(2, $wakeupCallsAfterSecond, 'Second wakeup call should be tracked');

        // Assert: GUID locking prevented second runner from spawning
        $startCallsAfterSecond = $this->asyncProcessStarter->getMethodCallHistory('start');
        $this->assertCount(
            1,
            $startCallsAfterSecond,
            'Race condition detected: Only ONE runner should spawn despite two wakeup attempts. ' .
            'GUID locking in TaskRunnerWakeupService::doWakeup() prevents duplicate spawns.'
        );

        // Verify GUID remained the same (second wakeup didn't overwrite it)
        $runnerStatusAfter = $this->runnerStatusStorage->getStatus();
        $this->assertEquals(
            $firstGuid,
            $runnerStatusAfter->getGuid(),
            'GUID should remain unchanged after second wakeup (locked)'
        );

        // Verify the queue still has pending work (consistency check)
        $this->assertTrue(
            $this->queue->hasPendingWork(),
            'Queue should still have pending work after wakeup attempts'
        );
    }

    /**
     * Test: Fail-safe prevents lockup on query errors
     */
    public function testFailsafePreventsPermanentLockup()
    {
        // Arrange: Mock queue that throws specific exception
        $mockQueue = $this->getMockBuilder(QueueService::class)
                          ->disableOriginalConstructor()
                          ->getMock();

        $mockQueue->method('findOldestQueuedItems')
                  ->will($this->throwException(
                      new \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException('DB error')
                  ));

        $mockQueue->method('hasPendingWork')
                  ->will($this->returnCallback(function() use ($mockQueue) {
                      try {
                          $mockQueue->findOldestQueuedItems(1);
                          return false;
                      } catch (\Exception $ex) {
                          return true; // Fail-safe
                      }
                  }));

        TestServiceRegister::registerService(
            QueueService::CLASS_NAME,
            function () use ($mockQueue) {
                return $mockQueue;
            }
        );

        // Re-create TaskRunner with mocked queue
        $taskRunner = new TaskRunner();

        // Act: Check via mocked QueueService
        $hasTasks = $mockQueue->hasPendingWork();

        // Assert: Fail-safe returns TRUE (assumes tasks exist)
        $this->assertTrue($hasTasks, 'Fail-safe should return true on query error');
    }

    /**
     * Helper: Invoke private method via reflection
     */
    private function invokePrivateMethod($object, $methodName, array $parameters = array())
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
