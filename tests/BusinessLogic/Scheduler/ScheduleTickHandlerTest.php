<?php

namespace Logeecom\Tests\BusinessLogic\Scheduler;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Infrastructure\TaskExecution\QueueService;
use Logeecom\Infrastructure\Utility\Events\EventBus;
use Logeecom\Infrastructure\Utility\TimeProvider;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryQueueItemRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryStorage;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestQueueService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestTaskRunnerWakeupService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestShopConfiguration;
use Logeecom\Tests\Infrastructure\Common\TestComponents\Utility\TestTimeProvider;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
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
     * @var QueueService
     */
    private $queue;
    /**
     * QueueStorage instance\
     *
     * @var \Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryQueueItemRepository
     */
    private $queueStorage;
    /**
     * TimeProvider instance
     *
     * @var \Logeecom\Tests\Infrastructure\Common\TestComponents\Utility\TestTimeProvider
     */
    private $timeProvider;
    /**
     * TaskRunnerWakeup instance
     *
     * @var TestTaskRunnerWakeupService
     */
    private $taskRunnerStarter;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function setUp()
    {
        parent::setUp();

        RepositoryRegistry::registerRepository(QueueItem::CLASS_NAME, MemoryQueueItemRepository::getClassName());

        /** @noinspection PhpUnhandledExceptionInspection */
        $timeProvider = new TestTimeProvider();
        $taskRunnerStarter = new TestTaskRunnerWakeupService();
        $queue = new TestQueueService();

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
                QueueService::CLASS_NAME => function () use ($queue) {
                    return $queue;
                },
                EventBus::CLASS_NAME => function () {
                    return EventBus::getInstance();
                },
            )
        );

        $this->queueStorage = RepositoryRegistry::getQueueItemRepository();
        $this->timeProvider = $timeProvider;
        $this->taskRunnerStarter = $taskRunnerStarter;
        $this->queue = $queue;
        MemoryStorage::reset();
    }

    /**
     * Tests queue of ScheduleCheckTask when queue is empty
     *
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\EntityClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function testFirstQueueOfHandler()
    {
        $tickHandler = new ScheduleTickHandler();
        $tickHandler->handle();

        /** @var \Logeecom\Infrastructure\TaskExecution\QueueItem[] $queueItems */
        $queueItems = $this->queueStorage->select();
        $this->assertCount(1, $queueItems);
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->assertEquals('ScheduleCheckTask', $queueItems[0]->getTaskType());
    }

    /**
     * Tests queue of ScheduleCheckTask when threshold is not up
     *
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\EntityClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function testSecondQueueOfHandler()
    {
        $tickHandler = new ScheduleTickHandler();
        $tickHandler->handle();

        /** @var \Logeecom\Infrastructure\TaskExecution\QueueItem[] $queueItems */
        $queueItems = $this->queueStorage->select();
        $this->assertCount(1, $queueItems);
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->assertEquals('ScheduleCheckTask', $queueItems[0]->getTaskType());


        $tickHandler->handle();

        $queueItems = $this->queueStorage->select();
        $this->assertCount(1, $queueItems);
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->assertEquals('ScheduleCheckTask', $queueItems[0]->getTaskType());
    }

    /**
     * Tests queue of ScheduleCheckTask when threshold is up
     *
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\EntityClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function testSecondQueueOfHandlerAfterThreshold()
    {
        $tickHandler = new ScheduleTickHandler();
        $tickHandler->handle();

        /** @var \Logeecom\Infrastructure\TaskExecution\QueueItem[] $queueItems */
        $queueItems = $this->queueStorage->select();
        $this->assertCount(1, $queueItems);
        /** @noinspection PhpUnhandledExceptionInspection */
        $queueItem = $queueItems[0];
        $this->assertEquals('ScheduleCheckTask', $queueItem->getTaskType());

        $queueItem->setQueueTimestamp($queueItem->getQueueTimestamp() - 61);
        $this->queueStorage->save($queueItem);
        $tickHandler->handle();

        $queueItems = $this->queueStorage->select();
        $this->assertCount(2, $queueItems);
    }
}
