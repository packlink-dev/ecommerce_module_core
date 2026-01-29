<?php
/** @noinspection PhpDuplicateArrayKeysInspection */

namespace Logeecom\Tests\Infrastructure\Scheduler;

use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\Serializer\Concrete\NativeSerializer;
use Logeecom\Infrastructure\Serializer\Serializer;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskExecutorInterface;
use Logeecom\Infrastructure\TaskExecution\Interfaces\QueueServiceInterface;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Infrastructure\TaskExecution\QueueService;
use Logeecom\Infrastructure\Utility\Events\EventBus;
use Logeecom\Infrastructure\Utility\TimeProvider;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\TestShopConfiguration;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryQueueItemRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryStorage;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestQueueService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestTaskRunnerWakeupService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestTaskExecutor;
use Logeecom\Tests\Infrastructure\Common\TestComponents\Utility\TestTimeProvider;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Logeecom\Infrastructure\Scheduler\ScheduleTickHandler;
use Packlink\BusinessLogic\Configuration;
use Logeecom\Infrastructure\Scheduler\QueueSchedulerCheckPolicy;
use Logeecom\Infrastructure\Scheduler\Interfaces\SchedulerCheckPolicyInterface;
use Logeecom\Infrastructure\Scheduler\ScheduleCheckTask;
use PHPUnit\Framework\TestCase;

/**
 * Class ScheduleCheckTaskTest
 * @package Logeecom\Tests\Infrastructure\Scheduler
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
     * @var TestTaskExecutor
     */
    private $taskExecutor;
    /**
     * @var QueueSchedulerCheckPolicy
     */
    private $checkPolicy;

    /**
     * @var TestShopConfiguration
     */
    private $config;

    /**
     * @before
     *
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function before()
    {
        $this->setUp();

        RepositoryRegistry::registerRepository(QueueItem::CLASS_NAME, MemoryQueueItemRepository::getClassName());

        /** @noinspection PhpUnhandledExceptionInspection */
        $timeProvider = new TestTimeProvider();
        $taskRunnerStarter = new TestTaskRunnerWakeupService();
        $queue = new TestQueueService();
        $config = new TestShopConfiguration();
        $taskExecutor = new TestTaskExecutor();
        $checkPolicy = new QueueSchedulerCheckPolicy($queue, $config);

        new TestServiceRegister(
            array(
                TimeProvider::CLASS_NAME => function () use ($timeProvider) {
                    return $timeProvider;
                },
                TaskRunnerWakeup::CLASS_NAME => function () use ($taskRunnerStarter) {
                    return $taskRunnerStarter;
                },
                Configuration::CLASS_NAME => function () use ($config) {
                    return $config;
                },
                QueueService::CLASS_NAME => function () use ($queue) {
                    return $queue;
                },
                QueueServiceInterface::CLASS_NAME => function () use ($queue) {
                    return $queue;
                },
                EventBus::CLASS_NAME => function () {
                    return EventBus::getInstance();
                },
                Serializer::CLASS_NAME => function() {
                    return new NativeSerializer();
                },
                TaskExecutorInterface::CLASS_NAME => function () use ($taskExecutor) {
                    return $taskExecutor;
                },
                SchedulerCheckPolicyInterface::CLASS_NAME => function () use ($checkPolicy) {
                    return $checkPolicy;
                },
            )
        );

        $this->queueStorage = RepositoryRegistry::getQueueItemRepository();
        $this->timeProvider = $timeProvider;
        $this->taskRunnerStarter = $taskRunnerStarter;
        $this->queue = $queue;
        $this->config = $config;
        $this->taskExecutor = $taskExecutor;
        $this->checkPolicy = $checkPolicy;
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
        $tickHandler = new ScheduleTickHandler($this->checkPolicy, $this->taskExecutor);
        $tickHandler->handle();

        $this->assertCount(1, $this->taskExecutor->enqueuedTasks);
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
        $tickHandler = new ScheduleTickHandler($this->checkPolicy, $this->taskExecutor);
        $tickHandler->handle();

        $this->assertCount(1, $this->taskExecutor->enqueuedTasks);

        // simulate queued task so policy blocks second enqueue
        $task = new ScheduleCheckTask();
        $this->queue->enqueue(
            $this->config->getSchedulerQueueName(),
            $task,
            $this->config->getContext(),
            $task->getPriority()
        );

        $tickHandler->handle();

        $this->assertCount(1, $this->taskExecutor->enqueuedTasks);
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
        $tickHandler = new ScheduleTickHandler($this->checkPolicy, $this->taskExecutor);
        $tickHandler->handle();

        $this->assertCount(1, $this->taskExecutor->enqueuedTasks);

        $task = new ScheduleCheckTask();
        $queueItem = $this->queue->enqueue(
            $this->config->getSchedulerQueueName(),
            $task,
            $this->config->getContext(),
            $task->getPriority()
        );
        $queueItem->setQueueTimestamp($queueItem->getQueueTimestamp() - 61);
        $this->queueStorage->update($queueItem);
        $tickHandler->handle();

        $this->assertCount(2, $this->taskExecutor->enqueuedTasks);
    }
}
