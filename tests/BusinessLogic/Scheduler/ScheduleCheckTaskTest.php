<?php

namespace Logeecom\Tests\BusinessLogic\Scheduler;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\Logger\Interfaces\DefaultLoggerAdapter;
use Logeecom\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Infrastructure\TaskExecution\QueueService;
use Logeecom\Infrastructure\TaskExecution\Task;
use Logeecom\Infrastructure\Utility\Events\EventBus;
use Logeecom\Infrastructure\Utility\TimeProvider;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\TestShopConfiguration;
use Logeecom\Tests\Infrastructure\Common\TestComponents\Logger\TestDefaultLogger as DefaultLogger;
use Logeecom\Tests\Infrastructure\Common\TestComponents\Logger\TestShopLogger;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryQueueItemRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\FooTask;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestQueueService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestTaskRunnerWakeupService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\Utility\TestTimeProvider;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Scheduler\Models\DailySchedule;
use Packlink\BusinessLogic\Scheduler\Models\MonthlySchedule;
use Packlink\BusinessLogic\Scheduler\Models\Schedule;
use Packlink\BusinessLogic\Scheduler\Models\WeeklySchedule;
use Packlink\BusinessLogic\Scheduler\ScheduleCheckTask;
use PHPUnit\Framework\TestCase;

/**
 * Class ScheduleCheckTaskTest
 * @package Logeecom\Tests\BusinessLogic\Scheduler
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
     * @throws \Exception
     */
    public function setUp()
    {
        $this->oldTimeZone = date_default_timezone_get();
        date_default_timezone_set('UTC');

        $taskInstance = $this;

        /** @noinspection PhpUnhandledExceptionInspection */
        $nowDateTime = new \DateTime();
        $nowDateTime->setDate(2018, 3, 21);
        $nowDateTime->setTime(13, 42, 5);

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
                EventBus::CLASS_NAME => function () {
                    return EventBus::getInstance();
                },
            )
        );

        Logger::resetInstance();

        $this->syncTask = new ScheduleCheckTask();
        $this->timeProvider = $timeProvider;
        $this->queueStorage = RepositoryRegistry::getQueueItemRepository();

        /** @noinspection PhpUnhandledExceptionInspection */
        RepositoryRegistry::registerRepository(Schedule::CLASS_NAME, MemoryRepository::getClassName());
    }

    /**
     * @inheritdoc
     */
    public function tearDown()
    {
        date_default_timezone_set($this->oldTimeZone);
        parent::tearDown();
    }

    /**
     * Tests when there are no scheduled tasks.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\EntityClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function testEmptyExecution()
    {
        $this->syncTask->execute();
        $this->assertEmpty($this->queueStorage->select());
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\EntityClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function testSchedulingTasks()
    {
        $this->prepareScheduleTasks();

        /** @noinspection PhpUnhandledExceptionInspection */
        $nowDateTime = new \DateTime();
        $nowDateTime->setDate(2018, 3, 22);
        $nowDateTime->setTime(13, 42, 5);
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
        /** @noinspection PhpUnhandledExceptionInspection */
        $daily->setNextSchedule($daily->calculateNextSchedule());

        $weekly = new WeeklySchedule(new FooTask(), 'queueForWeeklyFoo');
        $weekly->setDay(4);
        /** @noinspection PhpUnhandledExceptionInspection */
        $weekly->setNextSchedule($weekly->calculateNextSchedule());

        $monthly = new MonthlySchedule(new FooTask(), 'queueForMonthlyFoo');
        $monthly->setDay(21);
        $monthly->setHour(13);
        $monthly->setMinute(42);
        /** @noinspection PhpUnhandledExceptionInspection */
        $monthly->setNextSchedule($monthly->calculateNextSchedule());

        $repository->save($daily);
        $repository->save($weekly);
        $repository->save($monthly);
    }
}
