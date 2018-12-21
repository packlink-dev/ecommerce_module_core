<?php

namespace Logeecom\Tests\BusinessLogic\Scheduler;

use Logeecom\Infrastructure\Interfaces\Exposed\TaskRunnerWakeup;
use Logeecom\Infrastructure\Configuration;
use Logeecom\Infrastructure\Interfaces\Required\TaskQueueStorage;
use Logeecom\Infrastructure\TaskExecution\Queue;
use Logeecom\Infrastructure\Utility\TimeProvider;
use Logeecom\Tests\Common\TestComponents\TaskExecution\TestQueue;
use Logeecom\Tests\Common\TestComponents\TestShopConfiguration;
use Logeecom\Tests\Common\TestComponents\TaskExecution\InMemoryTestQueueStorage;
use Logeecom\Tests\Common\TestComponents\TaskExecution\TestTaskRunnerWakeup;
use Logeecom\Tests\Common\TestComponents\Utility\TestTimeProvider;
use Logeecom\Tests\Common\TestServiceRegister;
use Packlink\BusinessLogic\Scheduler\ScheduleTickHandler;
use PHPUnit\Framework\TestCase;

/**
 * Class ScheduleCheckTaskTest
 * @package Logeecom\Tests\BusinessLogic\Scheduler
 */
class ScheduleTickHandlerTest extends TestCase
{
    /**
     * Queue instance
     *
     * @var Queue
     */
    private $queue;
    /**
     * QueueStorage instance\
     *
     * @var InMemoryTestQueueStorage
     */
    private $queueStorage;
    /**
     * TimeProvider instance
     *
     * @var TestTimeProvider
     */
    private $timeProvider;
    /**
     * TaskRunnerWakeup instance
     *
     * @var TestTaskRunnerWakeup
     */
    private $taskRunnerStarter;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();

        $queueStorage = new InMemoryTestQueueStorage();
        /** @noinspection PhpUnhandledExceptionInspection */
        $timeProvider = new TestTimeProvider();
        $taskRunnerStarter = new TestTaskRunnerWakeup();
        $queue = new TestQueue();

        new TestServiceRegister(
            array(
                TaskQueueStorage::CLASS_NAME => function () use ($queueStorage) {
                    return $queueStorage;
                },
                TimeProvider::CLASS_NAME => function () use ($timeProvider) {
                    return $timeProvider;
                },
                TaskRunnerWakeup::CLASS_NAME => function () use ($taskRunnerStarter) {
                    return $taskRunnerStarter;
                },
                Configuration::CLASS_NAME => function () {
                    return new TestShopConfiguration();
                },
                Queue::CLASS_NAME => function () use ($queue) {
                    return $queue;
                },
            )
        );

        $this->queueStorage = $queueStorage;
        $this->timeProvider = $timeProvider;
        $this->taskRunnerStarter = $taskRunnerStarter;
        $this->queue = $queue;
    }

    /**
     * Tests queue of ScheduleCheckTask when queue is empty
     *
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    public function testFirstQueueOfHandler()
    {
        $tickHandler = new ScheduleTickHandler();
        $tickHandler->handle();

        /** @var \Logeecom\Infrastructure\TaskExecution\QueueItem[] $queueItems */
        $queueItems = $this->queueStorage->findAll();
        $this->assertCount(1, $queueItems);
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->assertEquals('ScheduleCheckTask', $queueItems[0]->getTaskType());
    }

    /**
     * Tests queue of ScheduleCheckTask when threshold is not up
     *
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    public function testSecondQueueOfHandler()
    {
        $tickHandler = new ScheduleTickHandler();
        $tickHandler->handle();

        /** @var \Logeecom\Infrastructure\TaskExecution\QueueItem[] $queueItems */
        $queueItems = $this->queueStorage->findAll();
        $this->assertCount(1, $queueItems);
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->assertEquals('ScheduleCheckTask', $queueItems[0]->getTaskType());


        $tickHandler->handle();

        $queueItems = $this->queueStorage->findAll();
        $this->assertCount(1, $queueItems);
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->assertEquals('ScheduleCheckTask', $queueItems[0]->getTaskType());
    }

    /**
     * Tests queue of ScheduleCheckTask when threshold is up
     *
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemSaveException
     */
    public function testSecondQueueOfHandlerAfterThreshold()
    {
        $tickHandler = new ScheduleTickHandler();
        $tickHandler->handle();

        /** @var \Logeecom\Infrastructure\TaskExecution\QueueItem[] $queueItems */
        $queueItems = $this->queueStorage->findAll();
        $this->assertCount(1, $queueItems);
        /** @noinspection PhpUnhandledExceptionInspection */
        $queueItem = $queueItems[0];
        $this->assertEquals('ScheduleCheckTask', $queueItem->getTaskType());

        $queueItem->setQueueTimestamp($queueItem->getQueueTimestamp() - 61);
        $this->queueStorage->save($queueItem);
        $tickHandler->handle();

        $queueItems = $this->queueStorage->findAll();
        $this->assertCount(2, $queueItems);
    }
}
