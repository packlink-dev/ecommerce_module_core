<?php
/** @noinspection PhpDuplicateArrayKeysInspection */

namespace Logeecom\Tests\Infrastructure\Scheduler;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\Logger\Interfaces\DefaultLoggerAdapter;
use Logeecom\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\Configuration\ConfigEntity;
use Logeecom\Infrastructure\ORM\QueryFilter\Operators;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\Serializer\Concrete\NativeSerializer;
use Logeecom\Infrastructure\Serializer\Serializer;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\AsyncProcessUrlProviderInterface;
use Logeecom\Infrastructure\TaskExecution\HttpTaskExecutor;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskExecutorInterface;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerConfigInterface;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskStatusProviderInterface;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Infrastructure\TaskExecution\QueueTaskStatusProvider;
use Logeecom\Infrastructure\TaskExecution\QueueService;
use Logeecom\Infrastructure\TaskExecution\Task;
use Logeecom\Infrastructure\Utility\Events\EventBus;
use Logeecom\Infrastructure\Utility\TimeProvider;
use Logeecom\Infrastructure\Scheduler\TaskRunnerScheduler;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\TestShopConfiguration;
use Logeecom\Tests\Infrastructure\Common\TestComponents\Logger\TestDefaultLogger as DefaultLogger;
use Logeecom\Tests\Infrastructure\Common\TestComponents\Logger\TestShopLogger;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryQueueItemRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryStorage;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\BarTask;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\FooTask;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestAsyncProcessUrlProvider;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestQueueService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestTaskRunnerConfig;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestTaskRunnerWakeupService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\Utility\TestTimeProvider;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Tasks\DefaultTaskMetadataProvider;
use Logeecom\Infrastructure\Scheduler\Models\DailySchedule;
use Logeecom\Infrastructure\Scheduler\Models\HourlySchedule;
use Logeecom\Infrastructure\Scheduler\Models\MonthlySchedule;
use Logeecom\Infrastructure\Scheduler\Models\Schedule;
use Logeecom\Infrastructure\Scheduler\Models\WeeklySchedule;
use Logeecom\Infrastructure\Scheduler\ScheduleCheckTask;
use PHPUnit\Framework\TestCase;

/**
 * Class ScheduleCheckTaskTest
 *
 * @package Logeecom\Tests\Infrastructure\Scheduler
 */
class ScheduleCheckTaskTest extends TestCase
{
    /**
     * @var \Logeecom\Tests\Infrastructure\Common\TestComponents\Utility\TestTimeProvider
     */
    public $timeProvider;
    /**
     * @var TestShopConfiguration
     */
    public $shopConfig;
    /**
     * @var TestShopLogger
     */
    public $shopLogger;
    /**
     * @var array
     */
    public $eventHistory;
    /**
     * @var Task
     */
    public $syncTask;
    /**
     * QueueItem repository instance
     *
     * @var MemoryQueueItemRepository
     */
    public $queueStorage;
    /**
     * @var string
     */
    private $oldTimeZone;

    /**
     * @before
     * @throws \Exception
     */
    public function before()
    {
        $this->oldTimeZone = date_default_timezone_get();
        date_default_timezone_set('UTC');

        $taskInstance = $this;

        $nowDateTime = new \DateTime('2018-03-21T13:42:05');

        $timeProvider = new TestTimeProvider();
        $timeProvider->setCurrentLocalTime($nowDateTime);
        $this->shopConfig = new TestShopConfiguration();
        $this->shopLogger = new TestShopLogger();
        $queue = new TestQueueService();
        $taskRunnerStarter = new TestTaskRunnerWakeupService();

        RepositoryRegistry::registerRepository(QueueItem::CLASS_NAME, MemoryQueueItemRepository::getClassName());

        new TestServiceRegister(
            array(
                Configuration::CLASS_NAME => function () use ($taskInstance) {
                    return $taskInstance->shopConfig;
                },
                AsyncProcessUrlProviderInterface::CLASS_NAME => function () {
                    return new TestAsyncProcessUrlProvider();
                },

                TaskRunnerConfigInterface::CLASS_NAME => function () {
                    $config = ServiceRegister::getService(Configuration::CLASS_NAME);
                    $urlProvider = ServiceRegister::getService(AsyncProcessUrlProviderInterface::CLASS_NAME);

                    return new TestTaskRunnerConfig($config, $urlProvider);
                },

                TimeProvider::CLASS_NAME => function () use ($timeProvider) {
                    return $timeProvider;
                },
                DefaultLoggerAdapter::CLASS_NAME => function () {
                    return new DefaultLogger();
                },
                ShopLoggerAdapter::CLASS_NAME => function () use ($taskInstance) {
                    return $taskInstance->shopLogger;
                },
                TaskRunnerWakeup::CLASS_NAME => function () use ($taskRunnerStarter) {
                    return $taskRunnerStarter;
                },
                QueueService::CLASS_NAME => function () use ($queue) {
                    return $queue;
                },
                TaskStatusProviderInterface::CLASS_NAME => function () use ($queue) {
                    return new QueueTaskStatusProvider($queue, $this->timeProvider);
                },
                TaskExecutorInterface::CLASS_NAME => function () use ($taskInstance, $queue, $timeProvider) {
                    $taskRunnerConfig = ServiceRegister::getService(
                        \Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerConfigInterface::CLASS_NAME
                    );

                    $metadataProvider = new DefaultTaskMetadataProvider($taskInstance->shopConfig, $taskRunnerConfig);

                    return new HttpTaskExecutor(
                        $queue,
                        $metadataProvider,
                        $taskInstance->shopConfig,
                        EventBus::getInstance(),
                        $timeProvider,
                        new TaskRunnerScheduler($taskInstance->shopConfig, $taskRunnerConfig),
                        $taskRunnerConfig
                    );
                },
                EventBus::CLASS_NAME => function () {
                    return EventBus::getInstance();
                },
                Serializer::CLASS_NAME => function() {
                    return new NativeSerializer();
                }
            )
        );

        Logger::resetInstance();

        $this->syncTask = new ScheduleCheckTask();
        $this->timeProvider = $timeProvider;
        $this->queueStorage = RepositoryRegistry::getQueueItemRepository();

        /** @noinspection PhpUnhandledExceptionInspection */
        RepositoryRegistry::registerRepository(ConfigEntity::CLASS_NAME, MemoryRepository::getClassName());
        /** @noinspection PhpUnhandledExceptionInspection */
        RepositoryRegistry::registerRepository(Schedule::CLASS_NAME, MemoryRepository::getClassName());
    }

    /**
     * @after
     * @return void
     */
    public function after()
    {
        date_default_timezone_set($this->oldTimeZone);
        MemoryStorage::reset();

        $this->tearDown();
    }

    /**
     * Tests when there are no scheduled tasks.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\EntityClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\AbortTaskExecutionException
     */
    public function testEmptyExecution()
    {
        $this->syncTask->execute();
        $this->assertEmpty($this->queueStorage->select());
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\EntityClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\AbortTaskExecutionException
     */
    public function testSchedulingTasks()
    {
        $this->prepareScheduleTasks();

        $nowDateTime = new \DateTime('2018-03-22T13:42:05');
        $this->timeProvider->setCurrentLocalTime($nowDateTime);
        $this->syncTask->execute();

        /** @var \Logeecom\Infrastructure\TaskExecution\QueueItem[] $queueItems */
        $queueItems = $this->queueStorage->select();
        $this->assertNotEmpty($queueItems);
        $this->assertCount(2, $queueItems);
        $this->assertEquals('queueForDailyFoo', $queueItems[0]->getQueueName());
        $this->assertEquals('queueForWeeklyFoo', $queueItems[1]->getQueueName());
    }

    /**
     * Tests execution of a delayed task.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\EntityClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\AbortTaskExecutionException
     */
    public function testDelayedTask()
    {
        $timestamp = strtotime('+5 minutes');

        $delayedTask = new HourlySchedule(new FooTask(), 'delayedQueue');
        $delayedTask->setMonth((int)date('m', $timestamp));
        $delayedTask->setDay((int)date('d', $timestamp));
        $delayedTask->setHour((int)date('H', $timestamp));
        $delayedTask->setMinute((int)date('i', $timestamp));
        $delayedTask->setRecurring(false);
        $delayedTask->setNextSchedule();

        /** @noinspection PhpUnhandledExceptionInspection */
        $scheduleRepository = RepositoryRegistry::getRepository(Schedule::CLASS_NAME);
        $id = $scheduleRepository->save($delayedTask);

        // Test that schedule exists.
        $filter = new QueryFilter();
        $filter->where('id', Operators::EQUALS, $id);
        $task = $scheduleRepository->selectOne($filter);

        self::assertNotNull($task);

        $newTimestamp = strtotime('+7 minutes');
        $newTime = $this->timeProvider->getDateTime($newTimestamp);
        $this->timeProvider->setCurrentLocalTime($newTime);

        $this->syncTask->execute();

        // Test that schedule has been deleted after one execution.
        $task = $scheduleRepository->selectOne($filter);

        self::assertNull($task);

        // Test that scheduled task exists.
        $filter = new QueryFilter();
        $filter->where('queueName', Operators::EQUALS, 'delayedQueue');

        $queuedTask = $this->queueStorage->selectOne($filter);
        self::assertNotNull($queuedTask);
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\EntityClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\AbortTaskExecutionException
     */
    public function testMultiSchedules()
    {
        $repository = RepositoryRegistry::getRepository(Schedule::CLASS_NAME);

        $schedule = new HourlySchedule(new FooTask());
        $schedule->setHour(13);
        $schedule->setNextSchedule();
        $repository->save($schedule);

        $nowDateTime = new \DateTime('2018-03-22T13:42:05');
        $this->timeProvider->setCurrentLocalTime($nowDateTime);
        $this->syncTask->execute();

        // run scheduler once again after the previous tasks is enqueued but not executed
        $nowDateTime = new \DateTime('2018-03-22T15:42:05');
        $this->timeProvider->setCurrentLocalTime($nowDateTime);
        $this->syncTask->execute();

        /** @var \Logeecom\Infrastructure\TaskExecution\QueueItem[] $queueItems */
        $queueItems = $this->queueStorage->select();
        $this->assertCount(1, $queueItems);
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\EntityClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\AbortTaskExecutionException
     */
    public function testMultipleSchedulesForCompletedTask()
    {
        $this->multipleSchedulesTest(QueueItem::COMPLETED, 1);
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\EntityClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\AbortTaskExecutionException
     */
    public function testMultipleSchedulesForInProgressTask()
    {
        $this->multipleSchedulesTest(QueueItem::IN_PROGRESS, 0);
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\EntityClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\AbortTaskExecutionException
     */
    public function testMultipleSchedulesForFailedTask()
    {
        $this->multipleSchedulesTest(QueueItem::FAILED, 1);
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\EntityClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\AbortTaskExecutionException
     */
    public function testMultipleSchedulesForAbortedTask()
    {
        $this->multipleSchedulesTest(QueueItem::ABORTED, 1);
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\EntityClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\AbortTaskExecutionException
     */
    public function testEnqueuingAllNonRecurringScheduledTasks()
    {
        $repository = RepositoryRegistry::getRepository(Schedule::CLASS_NAME);

        $schedule = new HourlySchedule(new FooTask());
        $schedule->setRecurring(false);
        $schedule->setHour(13);
        $schedule->setNextSchedule();
        $repository->save($schedule);

        $schedule = new HourlySchedule(new FooTask());
        $schedule->setRecurring(false);
        $schedule->setHour(14);
        $schedule->setNextSchedule();
        $repository->save($schedule);

        $nowDateTime = new \DateTime('2018-03-22T14:42:05');
        $this->timeProvider->setCurrentLocalTime($nowDateTime);
        $this->syncTask->execute();

        /** @var \Logeecom\Infrastructure\TaskExecution\QueueItem[] $queueItems */
        $queueItems = $this->queueStorage->select();
        $this->assertCount(2, $queueItems);
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\EntityClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\AbortTaskExecutionException
     */
    public function testEnqueuingOnlyOneRecurringScheduledTask()
    {
        $repository = RepositoryRegistry::getRepository(Schedule::CLASS_NAME);

        $schedule = new HourlySchedule(new FooTask());
        $schedule->setRecurring(true);
        $schedule->setHour(13);
        $schedule->setNextSchedule();
        $repository->save($schedule);

        $schedule = new HourlySchedule(new FooTask());
        $schedule->setRecurring(true);
        $schedule->setHour(14);
        $schedule->setNextSchedule();
        $repository->save($schedule);

        $nowDateTime = new \DateTime('2018-03-22T14:42:05');
        $this->timeProvider->setCurrentLocalTime($nowDateTime);
        $this->syncTask->execute();

        /** @var \Logeecom\Infrastructure\TaskExecution\QueueItem[] $queueItems */
        $queueItems = $this->queueStorage->select();
        $this->assertCount(1, $queueItems);
    }

    /**
     * @param string $newStatus
     * @param int $expectedCount
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\EntityClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\AbortTaskExecutionException
     */
    private function multipleSchedulesTest($newStatus, $expectedCount)
    {
        $this->testMultiSchedules();

        /** @var \Logeecom\Infrastructure\TaskExecution\QueueItem[] $queueItems */
        $queueItems = $this->queueStorage->select();
        $this->assertCount(1, $queueItems);

        // set task as in progress
        $queueItems[0]->setStatus($newStatus);
        $this->queueStorage->update($queueItems[0]);

        $filter = new QueryFilter();
        $filter->where('status', Operators::EQUALS, QueueItem::QUEUED);
        $queueItems = $this->queueStorage->select($filter);
        // make sure there are no queued items before execution
        $this->assertEmpty($queueItems);

        // run scheduler once again
        $this->syncTask->execute();

        $queueItems = $this->queueStorage->select($filter);
        $this->assertCount($expectedCount, $queueItems);
    }

    /**
     * Prepares scheduled tasks
     */
    private function prepareScheduleTasks()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $repository = RepositoryRegistry::getRepository(Schedule::CLASS_NAME);
        $daily = new DailySchedule(new FooTask(), 'queueForDailyFoo');
        $daily->setHour(13);
        $daily->setMinute(40);
        $daily->setDaysOfWeek(array(1, 2, 3, 4, 5));
        $daily->setNextSchedule();

        $weekly = new WeeklySchedule(new BarTask(), 'queueForWeeklyFoo');
        $weekly->setDay(4);
        $weekly->setNextSchedule();

        $monthly = new MonthlySchedule(new FooTask(), 'queueForMonthlyFoo');
        $monthly->setDay(23);
        $monthly->setHour(13);
        $monthly->setMinute(42);
        $monthly->setNextSchedule();

        $repository->save($daily);
        $repository->save($weekly);
        $repository->save($monthly);
    }
}
