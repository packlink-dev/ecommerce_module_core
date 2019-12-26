<?php
/** @noinspection PhpDuplicateArrayKeysInspection */

namespace Logeecom\Tests\Infrastructure\TaskExecution;

use Logeecom\Infrastructure\Configuration\ConfigEntity;
use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\Serializer\Concrete\NativeSerializer;
use Logeecom\Infrastructure\Serializer\Serializer;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Infrastructure\TaskExecution\QueueService;
use Logeecom\Infrastructure\TaskExecution\Task;
use Logeecom\Infrastructure\Utility\Events\EventBus;
use Logeecom\Infrastructure\Utility\TimeProvider;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryQueueItemRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryStorage;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\AbortTask;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\BarTask;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\FooTask;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestTaskRunnerWakeupService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestShopConfiguration;
use Logeecom\Tests\Infrastructure\Common\TestComponents\Utility\TestTimeProvider;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use PHPUnit\Framework\TestCase;

/**
 * Class QueueTest
 *
 * @package Logeecom\Tests\Infrastructure\TaskExecution
 */
class QueueTest extends TestCase
{
    /** @var QueueService */
    public $queue;
    /** @var \Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryQueueItemRepository */
    public $queueStorage;
    /** @var TestTimeProvider */
    public $timeProvider;
    /** @var \Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestTaskRunnerWakeupService */
    public $taskRunnerStarter;

    /**
     * @throws \Exception
     */
    public function setUp()
    {
        RepositoryRegistry::registerRepository(QueueItem::CLASS_NAME, MemoryQueueItemRepository::getClassName());
        RepositoryRegistry::registerRepository(ConfigEntity::CLASS_NAME, MemoryRepository::getClassName());

        $timeProvider = new TestTimeProvider();
        $taskRunnerStarter = new TestTaskRunnerWakeupService();

        new TestServiceRegister(
            array(
                TimeProvider::CLASS_NAME => function () use ($timeProvider) {
                    return $timeProvider;
                },
                TaskRunnerWakeup::CLASS_NAME => function () use ($taskRunnerStarter) {
                    return $taskRunnerStarter;
                },
                Configuration::CLASS_NAME => function () {
                    return new TestShopConfiguration();
                },
                EventBus::CLASS_NAME => function () {
                    return EventBus::getInstance();
                },
                Serializer::CLASS_NAME => function () {
                    return new NativeSerializer();
                },
            )
        );

        $this->queueStorage = RepositoryRegistry::getQueueItemRepository();
        $this->timeProvider = $timeProvider;
        $this->taskRunnerStarter = $taskRunnerStarter;
        $this->queue = new QueueService();
        MemoryStorage::reset();
    }

    /**
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function testItShouldBePossibleToFindQueueItemById()
    {
        $queueItem = $this->queue->enqueue(
            'testQueue',
            new FooTask()
        );

        $foundQueueItem = $this->queue->find($queueItem->getId());

        $this->assertEquals(
            $queueItem->getId(),
            $foundQueueItem->getId(),
            'Finding queue item by id must return queue item with given id.'
        );
        $this->assertEquals(
            $queueItem->getStatus(),
            $foundQueueItem->getStatus(),
            'Finding queue item by id must return queue item with given id.'
        );
        $this->assertEquals(
            $queueItem->getQueueName(),
            $foundQueueItem->getQueueName(),
            'Finding queue item by id must return queue item with given id.'
        );
        $this->assertEquals(
            $queueItem->getLastExecutionProgressBasePoints(),
            $foundQueueItem->getLastExecutionProgressBasePoints(),
            'Finding queue item by id must return queue item with given id.'
        );
        $this->assertEquals(
            $queueItem->getProgressBasePoints(),
            $foundQueueItem->getProgressBasePoints(),
            'Finding queue item by id must return queue item with given id.'
        );
        $this->assertEquals(
            $queueItem->getRetries(),
            $foundQueueItem->getRetries(),
            'Finding queue item by id must return queue item with given id.'
        );
        $this->assertEquals(
            $queueItem->getFailureDescription(),
            $foundQueueItem->getFailureDescription(),
            'Finding queue item by id must return queue item with given id.'
        );
        $this->assertEquals(
            $queueItem->getCreateTimestamp(),
            $foundQueueItem->getCreateTimestamp(),
            'Finding queue item by id must return queue item with given id.'
        );
        $this->assertEquals(
            $queueItem->getQueueTimestamp(),
            $foundQueueItem->getQueueTimestamp(),
            'Finding queue item by id must return queue item with given id.'
        );
        $this->assertEquals(
            $queueItem->getLastUpdateTimestamp(),
            $foundQueueItem->getLastUpdateTimestamp(),
            'Finding queue item by id must return queue item with given id.'
        );
        $this->assertEquals(
            $queueItem->getStartTimestamp(),
            $foundQueueItem->getStartTimestamp(),
            'Finding queue item by id must return queue item with given id.'
        );
        $this->assertEquals(
            $queueItem->getFinishTimestamp(),
            $foundQueueItem->getFinishTimestamp(),
            'Finding queue item by id must return queue item with given id.'
        );
        $this->assertEquals(
            $queueItem->getFailTimestamp(),
            $foundQueueItem->getFailTimestamp(),
            'Finding queue item by id must return queue item with given id.'
        );
        $this->assertEquals(
            $queueItem->getEarliestStartTimestamp(),
            $foundQueueItem->getEarliestStartTimestamp(),
            'Finding queue item by id must return queue item with given id.'
        );
    }

    /**
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function testItShouldBePossibleToFindRunningQueueItems()
    {
        // Arrange
        $runningItem1 = $this->generateRunningQueueItem('testQueue', new FooTask());
        $runningItem2 = $this->generateRunningQueueItem(
            'testQueue',
            new FooTask()
        );
        $runningItem3 = $this->generateRunningQueueItem(
            'otherQueue',
            new FooTask()
        );
        $this->queue->enqueue(
            'testQueue',
            new FooTask()
        );
        $this->queue->enqueue(
            'otherQueue',
            new FooTask()
        );
        $this->queue->enqueue(
            'withoutRunningItemsQueue',
            new FooTask()
        );
        $queue = new QueueService();

        // Act
        $result = $queue->findRunningItems();

        // Assert
        $this->assertCount(3, $result);
        $this->assertTrue(
            $this->inArrayQueueItem($runningItem1, $result),
            'Find running queue items should contain all running queue items in queue.'
        );
        $this->assertTrue(
            $this->inArrayQueueItem($runningItem2, $result),
            'Find running queue items should contain all running queue items in queue.'
        );
        $this->assertTrue(
            $this->inArrayQueueItem($runningItem3, $result),
            'Find running queue items should contain all running queue items in queue.'
        );
    }

    /**
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Exception
     */
    public function testFindOldestQueuedItems()
    {
        // Arrange
        $this->timeProvider->setCurrentLocalTime(new \DateTime('now -3 days'));
        $earliestQueue1Item = $this->queue->enqueue(
            'queue1',
            new FooTask()
        );
        $earliestQueue2Item = $this->queue->enqueue(
            'queue2',
            new FooTask()
        );

        $this->generateRunningQueueItem(
            'queue3',
            new FooTask()
        );

        $this->timeProvider->setCurrentLocalTime(new \DateTime('now -2 days'));
        $this->queue->enqueue(
            'queue1',
            new FooTask()
        );
        $this->queue->enqueue(
            'queue2',
            new FooTask()
        );
        $this->queue->enqueue(
            'queue3',
            new FooTask()
        );

        // Act
        $result = $this->queue->findOldestQueuedItems();

        // Assert
        $this->assertCount(
            2,
            $result,
            'Find earliest queued items should contain only earliest queued items from all queues.'
        );
        $this->assertTrue(
            $this->inArrayQueueItem($earliestQueue1Item, $result),
            'Find earliest queued items should contain only earliest queued items from all queues.'
        );
        $this->assertTrue(
            $this->inArrayQueueItem($earliestQueue2Item, $result),
            'Find earliest queued items should contain only earliest queued items from all queues.'
        );
    }

    /**
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Exception
     */
    public function testFindLatestByType()
    {
        // Arrange
        $this->timeProvider->setCurrentLocalTime(new \DateTime('now -3 days'));
        $this->queue->enqueue(
            'queue1',
            new FooTask(),
            'context'
        );
        $this->queue->enqueue('queue2', new FooTask(), 'context');

        $this->timeProvider->setCurrentLocalTime(new \DateTime('now -2 days'));
        $latestQueueItem = $this->queue->enqueue(
            'queue1',
            new FooTask(),
            'context'
        );

        $this->timeProvider->setCurrentLocalTime(new \DateTime('now -1 days'));
        $this->queue->enqueue('queue1', new BarTask(), 'context');
        $globallyLatestQueueItem = $this->queue->enqueue(
            'queue1',
            new FooTask(),
            'different context'
        );

        // Act
        $result = $this->queue->findLatestByType('FooTask', 'context');
        $globalResult = $this->queue->findLatestByType('FooTask');

        // Assert
        $this->assertNotNull(
            $result,
            'Find latest by type should contain latest queued item from all queues with given type in given context.'
        );
        $this->assertNotNull(
            $globalResult,
            'Find latest by type should contain latest queued item from all queues with given type.'
        );
        $this->assertSame(
            $latestQueueItem->getId(),
            $result->getId(),
            'Find latest by type should return latest queued item with given type from all queues in given context.'
        );
        $this->assertSame(
            $globallyLatestQueueItem->getId(),
            $globalResult->getId(),
            'Find latest by type should return latest queued item with given type from all queues.'
        );
    }

    /**
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Exception
     */
    public function testLimitOfFinOldestQueuedItems()
    {
        // Arrange
        $this->timeProvider->setCurrentLocalTime(new \DateTime('now -2 days'));
        $this->queue->enqueue(
            'queue5',
            new FooTask()
        );
        $this->timeProvider->setCurrentLocalTime(new \DateTime('now -3 days'));
        $this->queue->enqueue(
            'queue4',
            new FooTask()
        );
        $this->timeProvider->setCurrentLocalTime(new \DateTime('now -4 days'));
        $earliestQueue3Item = $this->queue->enqueue(
            'queue3',
            new FooTask()
        );
        $this->timeProvider->setCurrentLocalTime(new \DateTime('now -5 days'));
        $earliestQueue2Item = $this->queue->enqueue('queue2', new FooTask());
        $this->timeProvider->setCurrentLocalTime(new \DateTime('now -6 days'));
        $earliestQueue1Item = $this->queue->enqueue(
            'queue1',
            new FooTask()
        );
        $queue = new QueueService();

        // Act
        $result = $queue->findOldestQueuedItems(3);

        // Assert
        $this->assertCount(3, $result, 'Find earliest queued items should be limited.');
        $this->assertTrue(
            $this->inArrayQueueItem($earliestQueue1Item, $result),
            'Find earliest queued items should contain only earliest queued items from all queues.'
        );
        $this->assertTrue(
            $this->inArrayQueueItem($earliestQueue2Item, $result),
            'Find earliest queued items should contain only earliest queued items from all queues.'
        );
        $this->assertTrue(
            $this->inArrayQueueItem($earliestQueue3Item, $result),
            'Find earliest queued items should contain only earliest queued items from all queues.'
        );
    }

    /**
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Exception
     */
    public function testItShouldBePossibleEnqueueTaskToQueue()
    {
        // Arrange
        $currentTime = new \DateTime();
        $this->timeProvider->setCurrentLocalTime($currentTime);

        // Act
        $queueItem = $this->queue->enqueue(
            'testQueue',
            new FooTask()
        );

        // Assert
        $this->assertEquals(
            QueueItem::QUEUED,
            $queueItem->getStatus(),
            'When queued queue item must set status to "queued".'
        );
        $this->assertNotNull($queueItem->getId(), 'When queued queue item should be in storage. Id must not be null.');
        $this->assertArrayHasKey(
            $queueItem->getId(),
            MemoryStorage::$storage,
            'When queued queue item should be in storage.'
        );
        $this->assertEquals(
            'testQueue',
            $queueItem->getQueueName(),
            'When queued queue item should be in storage under given queue name.'
        );
        $this->assertSame(
            0,
            $queueItem->getLastExecutionProgressBasePoints(),
            'When queued queue item should NOT change last execution progress.'
        );
        $this->assertSame(0, $queueItem->getProgressBasePoints(), 'When queued queue item should NOT change progress.');
        $this->assertSame(0, $queueItem->getRetries(), 'When queued queue item must NOT change retries.');
        $this->assertSame(
            '',
            $queueItem->getFailureDescription(),
            'When queued queue item must NOT change failure description.'
        );
        $this->assertSame(
            $currentTime->getTimestamp(),
            $queueItem->getCreateTimestamp(),
            'When queued queue item must set create time.'
        );
        $this->assertSame(
            $currentTime->getTimestamp(),
            $queueItem->getQueueTimestamp(),
            'When queued queue item must record queue time.'
        );
        $this->assertNull($queueItem->getStartTimestamp(), 'When queued queue item must NOT change start time.');
        $this->assertNull($queueItem->getFinishTimestamp(), 'When queued queue item must NOT change finish time.');
        $this->assertNull($queueItem->getFailTimestamp(), 'When queued queue item must NOT change fail time.');
        $this->assertNull(
            $queueItem->getEarliestStartTimestamp(),
            'When queued queue item must NOT change earliest start time.'
        );
        $this->assertQueueItemIsSaved($queueItem);
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\EntityClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    public function testItShouldBePossibleToEnqueueTaskInSpecificContext()
    {
        // Arrange
        $queueItem = $this->queue->enqueue(
            'testQueue',
            new FooTask(),
            'test'
        );
        $this->assertSame(
            'test',
            $queueItem->getContext(),
            'When queued in specific context queue item context must match provided context.'
        );
        $this->assertQueueItemIsSaved($queueItem);
    }

    /**
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function testTaskEnqueueShouldWakeupTaskRunner()
    {
        // Act
        $this->queue->enqueue(
            'testQueue',
            new FooTask()
        );

        // Assert
        $wakeupCallHistory = $this->taskRunnerStarter->getMethodCallHistory('wakeup');
        $this->assertCount(1, $wakeupCallHistory, 'Task enqueue must wakeup task runner.');
    }

    /**
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Exception
     */
    public function testItShouldBePossibleToTransitToInProgressStateFromQueued()
    {
        // Arrange
        $task = new FooTask();

        $queuedTime = new \DateTime('now -2 days');
        $this->timeProvider->setCurrentLocalTime($queuedTime);
        $queueItem = $this->queue->enqueue('testQueue', $task);

        $startTime = new \DateTime('now -1 day');
        $this->timeProvider->setCurrentLocalTime($startTime);

        // Act
        $this->queue->start($queueItem);

        // Assert
        $this->assertSame(
            1,
            $task->getMethodCallCount('execute'),
            'When started queue item must call task execute method.'
        );
        $this->assertEquals(
            QueueItem::IN_PROGRESS,
            $queueItem->getStatus(),
            'When started queue item must set status to "in_progress".'
        );
        $this->assertSame(0, $queueItem->getRetries(), 'When started queue item must NOT change retries.');
        $this->assertSame(
            '',
            $queueItem->getFailureDescription(),
            'When started queue item must NOT change failure message.'
        );
        $this->assertSame(
            $queuedTime->getTimestamp(),
            $queueItem->getQueueTimestamp(),
            'When started queue item must NOT change queue time.'
        );
        $this->assertSame(
            $startTime->getTimestamp(),
            $queueItem->getStartTimestamp(),
            'When started queue item must record start time.'
        );
        $this->assertSame(
            $startTime->getTimestamp(),
            $queueItem->getLastUpdateTimestamp(),
            'When started queue item must set last update time.'
        );
        $this->assertNull($queueItem->getFinishTimestamp(), 'When started queue item must NOT finish time.');
        $this->assertNull($queueItem->getFailTimestamp(), 'When started queue item must NOT change fail time.');
        $this->assertNull(
            $queueItem->getEarliestStartTimestamp(),
            'When started queue item must NOT change earliest start time.'
        );
        $this->assertQueueItemIsSaved($queueItem);
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\EntityClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function testWhenInProgressReportedProgressShouldBeStoredUsingQueue()
    {
        // Arrange
        $task = new FooTask();
        $queueItem = $this->queue->enqueue('testQueue', $task);
        $this->queue->start($queueItem);

        // Act
        $task->reportProgress(10.12);

        // Assert
        $this->assertSame(
            1012,
            $queueItem->getProgressBasePoints(),
            'When started queue item must update task progress.'
        );
        $this->assertQueueItemIsSaved($queueItem);
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Progress reported for not started queue item.
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    public function testWhenNotInProgressReportedProgressShouldFailJob()
    {
        // Arrange
        $task = new FooTask();
        $queueItem = $this->queue->enqueue('testQueue', $task);
        $this->queue->start($queueItem);
        $this->queue->fail($queueItem, 'Test failure description');

        // Act
        $task->reportProgress(25.78);

        // Assert
        $this->fail('Reporting progress on not started queue item should fail.');
    }

    /**
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Exception
     */
    public function testWhenInProgressReportedAliveShouldBeStoredWithCurrentTimeAsLastUpdatedTimestamp()
    {
        // Arrange
        $task = new FooTask();
        $queueItem = $this->queue->enqueue('testQueue', $task);
        $this->queue->start($queueItem);

        $lastSaveTime = new \DateTime();
        $this->timeProvider->setCurrentLocalTime($lastSaveTime);

        // Act
        $task->reportAlive();

        // Assert
        $this->assertSame(
            $lastSaveTime->getTimestamp(),
            $queueItem->getLastUpdateTimestamp(),
            'When task alive reported queue item must be stored.'
        );
        $this->assertQueueItemIsSaved($queueItem);
    }

    /**
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Exception
     */
    public function testItShouldBePossibleToTransitToCompletedStateFromInProgress()
    {
        // Arrange
        $task = new FooTask();

        $queuedTime = new \DateTime('now -3 days');
        $this->timeProvider->setCurrentLocalTime($queuedTime);
        $queueItem = $this->queue->enqueue('testQueue', $task);

        $startTime = new \DateTime('now -2 days');
        $this->timeProvider->setCurrentLocalTime($startTime);
        $this->queue->start($queueItem);

        $finishTime = new \DateTime('now -1 day');
        $this->timeProvider->setCurrentLocalTime($finishTime);

        // Act
        $this->queue->finish($queueItem);

        // Assert
        $this->assertEquals(
            QueueItem::COMPLETED,
            $queueItem->getStatus(),
            'When finished queue item must set status to "completed".'
        );
        $this->assertSame(0, $queueItem->getRetries(), 'When finished queue item must NOT change retries.');
        $this->assertSame(
            10000,
            $queueItem->getProgressBasePoints(),
            'When finished queue item must ensure 100% progress value.'
        );
        $this->assertSame(
            '',
            $queueItem->getFailureDescription(),
            'When finished queue item must NOT change failure message.'
        );
        $this->assertSame(
            $queuedTime->getTimestamp(),
            $queueItem->getQueueTimestamp(),
            'When finished queue item must NOT change queue time.'
        );
        $this->assertSame(
            $startTime->getTimestamp(),
            $queueItem->getStartTimestamp(),
            'When finished queue item must NOT change start time.'
        );
        $this->assertSame(
            $finishTime->getTimestamp(),
            $queueItem->getFinishTimestamp(),
            'When finished queue item must record finish time.'
        );
        $this->assertNull($queueItem->getFailTimestamp(), 'When finished queue item must NOT change fail time.');
        $this->assertNull(
            $queueItem->getEarliestStartTimestamp(),
            'When finished queue item must NOT change earliest start time.'
        );
        $this->assertQueueItemIsSaved($queueItem);
    }

    /**
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Exception
     */
    public function testRequeueStartedTaskShouldReturnQueueItemInQueuedState()
    {
        // Arrange
        $task = new FooTask();

        $queuedTime = new \DateTime('now -3 days');
        $this->timeProvider->setCurrentLocalTime($queuedTime);
        $queueItem = $this->queue->enqueue('testQueue', $task);

        $startTime = new \DateTime('now -2 days');
        $this->timeProvider->setCurrentLocalTime($startTime);
        $this->queue->start($queueItem);

        $queueItem->setProgressBasePoints(3081);

        // Act
        $this->queue->requeue($queueItem);

        // Assert
        $this->assertEquals(
            QueueItem::QUEUED,
            $queueItem->getStatus(),
            'When requeue queue item must set status to "queued".'
        );
        $this->assertSame(0, $queueItem->getRetries(), 'When requeue queue item must not change retries count.');
        $this->assertSame(
            3081,
            $queueItem->getLastExecutionProgressBasePoints(),
            'When requeue queue item must set last execution progress to current queue item progress value.'
        );
        $this->assertSame(
            $queuedTime->getTimestamp(),
            $queueItem->getQueueTimestamp(),
            'When requeue queue item must NOT change queue time.'
        );
        $this->assertNull($queueItem->getStartTimestamp(), 'When requeue queue item must reset start time.');
        $this->assertNull($queueItem->getFinishTimestamp(), 'When requeue queue item must NOT change finish time.');
        $this->assertNull($queueItem->getFailTimestamp(), 'When requeue queue item must NOT change fail time.');
        $this->assertQueueItemIsSaved($queueItem);
    }

    /**
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Exception
     */
    public function testFailingLessThanMaxRetryTimesShouldReturnQueueItemInQueuedState()
    {
        // Arrange
        $task = new FooTask();

        $queuedTime = new \DateTime('now -3 days');
        $this->timeProvider->setCurrentLocalTime($queuedTime);
        $queueItem = $this->queue->enqueue('testQueue', $task);

        $startTime = new \DateTime('now -2 days');
        $this->timeProvider->setCurrentLocalTime($startTime);
        $queueItem->setLastExecutionProgressBasePoints(3123);
        $this->queue->start($queueItem);

        $failTime = new \DateTime('now -1 day');
        $this->timeProvider->setCurrentLocalTime($failTime);

        // Act
        for ($i = 0; $i < QueueService::MAX_RETRIES; $i++) {
            $this->queue->fail($queueItem, 'Test failure description');
            if ($i < QueueService::MAX_RETRIES - 1) {
                $this->queue->start($queueItem);
            }
        }

        // Assert
        $this->assertEquals(
            QueueItem::QUEUED,
            $queueItem->getStatus(),
            'When failed less than max retry times queue item must set status to "queued".'
        );
        $this->assertSame(
            5,
            $queueItem->getRetries(),
            'When failed queue item must increase retries by one up to max retries count.'
        );
        $this->assertSame(
            3123,
            $queueItem->getLastExecutionProgressBasePoints(),
            'When failed queue item must NOT reset last execution progress value.'
        );
        $this->assertStringStartsWith(
            'Attempt 1: Test failure description',
            $queueItem->getFailureDescription(),
            'When failed queue item must set failure description.'
        );
        $this->assertSame(
            $queuedTime->getTimestamp(),
            $queueItem->getQueueTimestamp(),
            'When failed queue item must NOT change queue time.'
        );
        $this->assertNull($queueItem->getStartTimestamp(), 'When failed queue item must reset start time.');
        $this->assertNull($queueItem->getFinishTimestamp(), 'When failed queue item NOT change finish time.');
        $this->assertNull(
            $queueItem->getFailTimestamp(),
            'When failed less than max retry times queue item must NOT change fail time.'
        );
        $this->assertQueueItemIsSaved($queueItem);
    }

    /**
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Exception
     */
    public function testFailingMoreThanMaxRetryTimesShouldTransitQueueItemInFailedState()
    {
        // Arrange
        $task = new FooTask();

        $queuedTime = new \DateTime('now -3 days');
        $this->timeProvider->setCurrentLocalTime($queuedTime);
        $queueItem = $this->queue->enqueue('testQueue', $task);

        $this->timeProvider->setCurrentLocalTime(new \DateTime('now -2 days'));
        $this->queue->start($queueItem);

        $failTime = new \DateTime('now -1 day');
        $this->timeProvider->setCurrentLocalTime($failTime);

        // Act
        for ($i = 0; $i <= QueueService::MAX_RETRIES; $i++) {
            $this->queue->fail($queueItem, 'Test failure description');
            if ($i < QueueService::MAX_RETRIES) {
                $this->queue->start($queueItem);
            }
        }

        // Assert
        $this->assertEquals(
            QueueItem::FAILED,
            $queueItem->getStatus(),
            'When failed more than max retry times queue item must set status to "failed".'
        );
        $this->assertSame(
            6,
            $queueItem->getRetries(),
            'When failed queue item must increase retries by one up to max retries count.'
        );
        $this->assertStringStartsWith(
            'Attempt 1: Test failure description',
            $queueItem->getFailureDescription(),
            'When failed queue item must set failure description.'
        );
        $this->assertSame(
            $queuedTime->getTimestamp(),
            $queueItem->getQueueTimestamp(),
            'When failed queue item must NOT change queue time.'
        );
        $this->assertNull($queueItem->getFinishTimestamp(), 'When failed queue item NOT change finish time.');
        $this->assertSame(
            $failTime->getTimestamp(),
            $queueItem->getFailTimestamp(),
            'When failed more than max retry times queue item must set fail time.'
        );
        $this->assertQueueItemIsSaved($queueItem);
    }

    /**
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Exception
     */
    public function testFailMessages()
    {
        $task = new FooTask();

        $queueItem = $this->queue->enqueue('testQueue', $task);
        $this->queue->start($queueItem);

        for ($i = 0; $i <= QueueService::MAX_RETRIES; $i++) {
            $this->queue->fail($queueItem, 'Test' . $i);
            if ($i < QueueService::MAX_RETRIES) {
                $this->queue->start($queueItem);
            }
        }

        $this->assertEquals(
            "Attempt 1: Test0\nAttempt 2: Test1\nAttempt 3: Test2\nAttempt 4: Test3\nAttempt 5: Test4\nAttempt 6: Test5",
            $queueItem->getFailureDescription(),
            'Failure descriptions must be stacked.'
        );
    }

    /**
     * Test regular task abort.
     */
    public function testAbortQueueItemMethod()
    {
        $queueItem = $this->queue->enqueue('testQueue', new FooTask());
        $this->queue->start($queueItem);
        $this->queue->abort($queueItem, 'Abort message.');

        $this->assertEquals(
            QueueItem::ABORTED,
            $queueItem->getStatus(),
            'The status for an aborted task must be set to "aborted".'
        );

        $this->assertNotEmpty($queueItem->getFailureDescription(), 'Abort message is missing.');
        $this->assertNotEmpty($queueItem->getFailTimestamp(), 'Fail timestamp should be set when aborting a task.');
    }

    /**
     * Test regular task abort.
     */
    public function testAbortingQueueItemFromTask()
    {
        $queueItem = $this->queue->enqueue('testQueue', new AbortTask());
        $this->queue->start($queueItem);

        $this->assertEquals(
            QueueItem::ABORTED,
            $queueItem->getStatus(),
            'The status for an aborted task must be set to "aborted".'
        );

        $this->assertEquals('Attempt 1: Abort mission!', $queueItem->getFailureDescription(), 'Wrong abort message.');
        $this->assertNotEmpty($queueItem->getFailTimestamp(), 'Fail timestamp should be set when aborting a task.');
    }

    /**
     * Test regular task abort.
     */
    public function testAbortingQueueItemAfterFailure()
    {
        $queueItem = $this->queue->enqueue('testQueue', new FooTask());
        $this->queue->start($queueItem);
        $this->queue->fail($queueItem, 'Fail message.');
        $this->queue->start($queueItem);
        $this->queue->abort($queueItem, 'Abort message.');

        $this->assertEquals(
            QueueItem::ABORTED,
            $queueItem->getStatus(),
            'The status for an aborted task must be set to "aborted".'
        );

        $this->assertEquals(
            "Attempt 1: Fail message.\nAttempt 2: Abort message.",
            $queueItem->getFailureDescription(),
            'Abort message should be appended to the failure message.'
        );
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testStartingQueueItemAfterAbortion()
    {
        $queueItem = $this->queue->enqueue('testQueue', new FooTask());
        $this->queue->start($queueItem);
        $this->queue->abort($queueItem, 'Abort message.');
        $this->queue->start($queueItem);

        $this->fail('Queue item should not be started after abortion.');
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Illegal queue item state transition from "created" to "in_progress"
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function testItShouldBeForbiddenToTransitionFromCreatedToInProgressStatus()
    {
        $queueItem = new QueueItem(new FooTask());

        $this->queue->start($queueItem);

        $this->fail('Queue item status transition from "created" to "in_progress" should not be allowed.');
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Illegal queue item state transition from "created" to "failed"
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function testItShouldBeForbiddenToTransitionFromCreatedToFailedStatus()
    {
        $queueItem = new QueueItem(new FooTask());

        $this->queue->fail($queueItem, 'Test failure description');

        $this->fail('Queue item status transition from "created" to "failed" should not be allowed.');
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Illegal queue item state transition from "created" to "completed"
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function testItShouldBeForbiddenToTransitionFromCreatedToCompletedStatus()
    {
        $queueItem = new QueueItem(new FooTask());

        $this->queue->finish($queueItem);

        $this->fail('Queue item status transition from "created" to "completed" should not be allowed.');
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Illegal queue item state transition from "queued" to "failed"
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function testItShouldBeForbiddenToTransitionFromQueuedToFailedStatus()
    {
        $queueItem = $this->queue->enqueue(
            'testQueue',
            new FooTask()
        );

        $this->queue->fail($queueItem, 'Test failure description');

        $this->fail('Queue item status transition from "queued" to "failed" should not be allowed.');
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Illegal queue item state transition from "queued" to "completed"
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function testItShouldBeForbiddenToTransitionFromQueuedToCompletedStatus()
    {
        $queueItem = $this->queue->enqueue(
            'testQueue',
            new FooTask()
        );

        $this->queue->finish($queueItem);

        $this->fail('Queue item status transition from "queued" to "completed" should not be allowed.');
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Illegal queue item state transition from "in_progress" to "in_progress"
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function testItShouldBeForbiddenToTransitionFromInProgressToInProgressStatus()
    {
        $queueItem = $this->queue->enqueue(
            'testQueue',
            new FooTask()
        );
        $this->queue->start($queueItem);

        $this->queue->start($queueItem);

        $this->fail('Queue item status transition from "in_progress" to "in_progress" should not be allowed.');
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Illegal queue item state transition from "failed" to "in_progress"
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function testItShouldBeForbiddenToTransitionFromFailedToInProgressStatus()
    {
        // Arrange
        $queueItem = $this->queue->enqueue(
            'testQueue',
            new FooTask()
        );
        $this->queue->start($queueItem);
        for ($i = 0; $i <= QueueService::MAX_RETRIES; $i++) {
            $this->queue->fail($queueItem, 'Test failure description');
            if ($i < QueueService::MAX_RETRIES) {
                $this->queue->start($queueItem);
            }
        }

        // Act
        $this->queue->start($queueItem);

        $this->fail('Queue item status transition from "failed" to "in_progress" should not be allowed.');
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Illegal queue item state transition from "failed" to "failed"
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function testItShouldBeForbiddenToTransitionFromFailedFailedStatus()
    {
        // Arrange
        $queueItem = $this->queue->enqueue(
            'testQueue',
            new FooTask()
        );
        $this->queue->start($queueItem);
        for ($i = 0; $i <= QueueService::MAX_RETRIES; $i++) {
            $this->queue->fail($queueItem, 'Test failure description');
            if ($i < QueueService::MAX_RETRIES) {
                $this->queue->start($queueItem);
            }
        }

        // Act
        $this->queue->fail($queueItem, 'Test failure description');

        $this->fail('Queue item status transition from "failed" to "failed" should not be allowed.');
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Illegal queue item state transition from "failed" to "completed"
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function testItShouldBeForbiddenToTransitionFromFailedCompletedStatus()
    {
        // Arrange
        $queueItem = $this->queue->enqueue(
            'testQueue',
            new FooTask()
        );
        $this->queue->start($queueItem);
        for ($i = 0; $i <= QueueService::MAX_RETRIES; $i++) {
            $this->queue->fail($queueItem, 'Test failure description');
            if ($i < QueueService::MAX_RETRIES) {
                $this->queue->start($queueItem);
            }
        }

        // Act
        $this->queue->finish($queueItem);

        $this->fail('Queue item status transition from "failed" to "completed" should not be allowed.');
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Illegal queue item state transition from "completed" to "in_progress"
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    public function testItShouldBeForbiddenToTransitionFromCompletedToInProgressStatus()
    {
        // Arrange
        $queueItem = $this->queue->enqueue(
            'testQueue',
            new FooTask()
        );
        $this->queue->start($queueItem);
        $this->queue->finish($queueItem);

        // Act
        $this->queue->start($queueItem);

        $this->fail('Queue item status transition from "completed" to "in_progress" should not be allowed.');
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Illegal queue item state transition from "completed" to "failed"
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function testItShouldBeForbiddenToTransitionFromCompletedToFailedStatus()
    {
        // Arrange
        $queueItem = $this->queue->enqueue(
            'testQueue',
            new FooTask()
        );
        $this->queue->start($queueItem);
        $this->queue->finish($queueItem);

        // Act
        $this->queue->fail($queueItem, 'Test failure description');

        $this->fail('Queue item status transition from "completed" to "failed" should not be allowed.');
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Illegal queue item state transition from "completed" to "completed"
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function testItShouldBeForbiddenToTransitionFromCompletedToCompletedStatus()
    {
        // Arrange
        $queueItem = $this->queue->enqueue(
            'testQueue',
            new FooTask()
        );
        $this->queue->start($queueItem);
        $this->queue->finish($queueItem);

        // Act
        $this->queue->finish($queueItem);

        $this->fail('Queue item status transition from "completed" to "completed" should not be allowed.');
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Illegal queue item state transition from "queued" to "aborted"
     */
    public function testItShouldBeForbiddenToTransitionFromQueuedToAbortedStatus()
    {
        $queueItem = $this->queue->enqueue('testQueue', new FooTask());

        $this->queue->abort($queueItem, '');

        $this->fail('Queue item status transition from "Created" to "Aborted" should not be allowed.');
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Illegal queue item state transition from "failed" to "aborted"
     */
    public function testItShouldBeForbiddenToTransitionFromFailedToAbortedStatus()
    {
        $queueItem = $this->queue->enqueue('testQueue', new FooTask());

        $this->queue->start($queueItem);
        for ($i = 0; $i <= QueueService::MAX_RETRIES; $i++) {
            $this->queue->fail($queueItem, 'Test failure description');
            if ($i < QueueService::MAX_RETRIES) {
                $this->queue->start($queueItem);
            }
        }
        $this->queue->abort($queueItem, '');

        $this->fail('Queue item status transition from "Failed" to "Aborted" should not be allowed.');
    }

    /**
     * @expectedException \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @expectedExceptionMessage Unable to update the task. Queue storage failed to save item.
     */
    public function testWhenStoringQueueItemFailsEnqueueMethodMustFail()
    {
        // Arrange
        $this->queueStorage->disabled = true;

        // Act
        $this->queue->enqueue(
            'testQueue',
            new FooTask()
        );

        $this->fail(
            'Enqueue queue item must fail with QueueStorageUnavailableException when queue storage save fails.'
        );
    }

    /**
     * @expectedException \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @expectedExceptionMessage Unable to update the task. Queue storage failed to save item.
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    public function testWhenStoringQueueItemFailsStartMethodMustFail()
    {
        // Arrange
        $queueItem = $this->queue->enqueue(
            'testQueue',
            new FooTask()
        );
        $this->queueStorage->disabled = true;

        // Act
        $this->queue->start($queueItem);

        $this->fail(
            'Starting queue item must fail with QueueStorageUnavailableException when queue storage save fails.'
        );
    }

    /**
     * @expectedException \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @expectedExceptionMessage Unable to update the task. Queue storage failed to save item.
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    public function testWhenStoringQueueItemFailsFailMethodMustFail()
    {
        // Arrange
        $queueItem = $this->queue->enqueue(
            'testQueue',
            new FooTask()
        );
        $this->queue->start($queueItem);
        $this->queueStorage->disabled = true;

        // Act
        $this->queue->fail($queueItem, 'Test failure description.');

        $this->fail(
            'Failing queue item must fail with QueueStorageUnavailableException when queue storage save fails.'
        );
    }

    /**
     * @expectedException \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @expectedExceptionMessage Unable to update the task. Queue storage failed to save item.
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    public function testWhenStoringQueueItemProgressFailsProgressMethodMustFail()
    {
        // Arrange
        $queueItem = $this->queue->enqueue(
            'testQueue',
            new FooTask()
        );
        $this->queue->start($queueItem);
        $this->queueStorage->disabled = true;

        // Act
        $this->queue->updateProgress($queueItem, 2095);

        $this->fail(
            'Queue item progress update must fail with QueueStorageUnavailableException when queue storage save fails.'
        );
    }

    /**
     * @expectedException \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @expectedExceptionMessage Unable to update the task. Queue storage failed to save item.
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    public function testWhenStoringQueueItemAliveFailsAliveMethodMustFail()
    {
        // Arrange
        $queueItem = $this->queue->enqueue('testQueue', new FooTask());
        $this->queue->start($queueItem);
        $this->queueStorage->disabled = true;

        // Act
        $this->queue->keepAlive($queueItem);

        $this->fail(
            'Queue item keep task alive signal must fail with QueueStorageUnavailableException when queue storage save fails.'
        );
    }

    /**
     * @expectedException \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @expectedExceptionMessage Unable to update the task. Queue storage failed to save item.
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    public function testWhenStoringQueueItemFailsFinishMethodMustFail()
    {
        // Arrange
        $queueItem = $this->queue->enqueue(
            'testQueue',
            new FooTask()
        );
        $this->queue->start($queueItem);
        $this->queueStorage->disabled = true;

        // Act
        $this->queue->finish($queueItem);

        $this->fail(
            'Finishing queue item must fail with QueueStorageUnavailableException when queue storage save fails.'
        );
    }

    /**
     * @param \Logeecom\Infrastructure\TaskExecution\QueueItem $queueItem
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\EntityClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    private function assertQueueItemIsSaved(QueueItem $queueItem)
    {
        $filter = new QueryFilter();
        $filter->where('id', '=', $queueItem->getId());
        /** @var QueueItem $storageItem */
        $storageItem = $this->queueStorage->selectOne($filter);

        $this->assertEquals(
            array(
                'id' => $queueItem->getId(),
                'status' => $queueItem->getStatus(),
                'type' => $queueItem->getTaskType(),
                'queueName' => $queueItem->getQueueName(),
                'context' => $queueItem->getContext(),
                'lastExecutionProgress' => $queueItem->getLastExecutionProgressBasePoints(),
                'progress' => $queueItem->getProgressBasePoints(),
                'retries' => $queueItem->getRetries(),
                'failureDescription' => $queueItem->getFailureDescription(),
                'createTimestamp' => $queueItem->getCreateTimestamp(),
                'queueTimestamp' => $queueItem->getQueueTimestamp(),
                'lastUpdateTimestamp' => $queueItem->getLastUpdateTimestamp(),
                'startTimestamp' => $queueItem->getStartTimestamp(),
                'finishTimestamp' => $queueItem->getFinishTimestamp(),
                'failTimestamp' => $queueItem->getFinishTimestamp(),
                'earliestStartTimestamp' => $queueItem->getEarliestStartTimestamp(),
            ),
            array(
                'id' => $storageItem->getId(),
                'status' => $storageItem->getStatus(),
                'type' => $storageItem->getTaskType(),
                'queueName' => $storageItem->getQueueName(),
                'context' => $storageItem->getContext(),
                'lastExecutionProgress' => $storageItem->getLastExecutionProgressBasePoints(),
                'progress' => $storageItem->getProgressBasePoints(),
                'retries' => $storageItem->getRetries(),
                'failureDescription' => $storageItem->getFailureDescription(),
                'createTimestamp' => $storageItem->getCreateTimestamp(),
                'queueTimestamp' => $storageItem->getQueueTimestamp(),
                'lastUpdateTimestamp' => $storageItem->getLastUpdateTimestamp(),
                'startTimestamp' => $storageItem->getStartTimestamp(),
                'finishTimestamp' => $storageItem->getFinishTimestamp(),
                'failTimestamp' => $storageItem->getFinishTimestamp(),
                'earliestStartTimestamp' => $storageItem->getEarliestStartTimestamp(),
            ),
            'Queue item storage data does not match queue item'
        );
    }

    /**
     * @param string $queueName
     * @param \Logeecom\Infrastructure\TaskExecution\Task $task
     *
     * @return \Logeecom\Infrastructure\TaskExecution\QueueItem
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    private function generateRunningQueueItem($queueName, Task $task)
    {
        $queueItem = $this->queue->enqueue($queueName, $task);
        $this->queue->start($queueItem);

        return $queueItem;
    }

    /**
     * Checks whether queue item is in array.
     *
     * @param \Logeecom\Infrastructure\TaskExecution\QueueItem $needle
     * @param array $haystack
     *
     * @return bool
     */
    private function inArrayQueueItem(QueueItem $needle, array $haystack)
    {
        /** @var QueueItem $queueItem */
        foreach ($haystack as $queueItem) {
            if ($queueItem->getId() === $needle->getId()) {
                return true;
            }
        }

        return false;
    }
}
