<?php

namespace Logeecom\Tests\BusinessLogic\Scheduler;

use Logeecom\Infrastructure\Configuration;
use Logeecom\Infrastructure\Interfaces\DefaultLoggerAdapter;
use Logeecom\Infrastructure\Interfaces\Exposed\TaskRunnerWakeup;
use Logeecom\Infrastructure\Interfaces\Required\ShopLoggerAdapter;
use Logeecom\Infrastructure\Interfaces\Required\TaskQueueStorage;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\TaskExecution\Queue;
use Logeecom\Infrastructure\TaskExecution\Task;
use Logeecom\Infrastructure\Utility\TimeProvider;
use Logeecom\Tests\Common\TestComponents\Logger\TestShopLogger;
use Logeecom\Tests\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Common\TestComponents\TaskExecution\FooTask;
use Logeecom\Tests\Common\TestComponents\TaskExecution\InMemoryTestQueueStorage;
use Logeecom\Tests\Common\TestComponents\TaskExecution\TestQueue;
use Logeecom\Tests\Common\TestComponents\TaskExecution\TestTaskRunnerWakeup;
use Logeecom\Tests\Common\TestComponents\TestShopConfiguration;
use Logeecom\Tests\Common\TestComponents\Utility\TestTimeProvider;
use Logeecom\Tests\Common\TestServiceRegister;
use Logeecom\Tests\Common\TestComponents\Logger\TestDefaultLogger as DefaultLogger;
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
     * @var TestTimeProvider
     */
    protected $timeProvider;
    /**
     * @var TestShopConfiguration
     */
    protected $shopConfig;
    /**
     * @var TestShopLogger
     */
    protected $shopLogger;
    /**
     * @var array
     */
    protected $eventHistory;
    /**
     * @var Task
     */
    protected $syncTask;
    /**
     * QueueStorage instance\
     *
     * @var InMemoryTestQueueStorage
     */
    private $queueStorage;

    /**
     * @throws \Exception
     */
    public function setUp()
    {
        $taskInstance = $this;

        /** @noinspection PhpUnhandledExceptionInspection */
        $nowDateTime = new \DateTime();
        $nowDateTime->setDate(2018, 3, 21);
        $nowDateTime->setTime(13, 42, 5);

        $timeProvider = new TestTimeProvider();
        $timeProvider->setCurrentLocalTime($nowDateTime);
        $this->shopConfig = new TestShopConfiguration();
        $this->shopLogger = new TestShopLogger();
        $queue = new TestQueue();
        $taskRunnerStarter = new TestTaskRunnerWakeup();
        $queueStorage = new InMemoryTestQueueStorage();

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
                TaskQueueStorage::CLASS_NAME => function () use ($queueStorage) {
                    return $queueStorage;
                },
                TaskRunnerWakeup::CLASS_NAME => function () use ($taskRunnerStarter) {
                    return $taskRunnerStarter;
                },
                Queue::CLASS_NAME => function () use ($queue) {
                    return $queue;
                },
            )
        );

        new Logger();

        $this->syncTask = new ScheduleCheckTask();
        $this->queueStorage = $queueStorage;
        $this->timeProvider = $timeProvider;

        /** @noinspection PhpUnhandledExceptionInspection */
        RepositoryRegistry::registerRepository(Schedule::CLASS_NAME, MemoryRepository::getClassName());
    }

    /**
     * Tests when there are no scheduled tasks
     */
    public function testEmptyExecution()
    {
        $this->syncTask->execute();
        $this->assertEmpty($this->queueStorage->findAll());
    }

    /**
     *
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
        $queueItems = $this->queueStorage->findAll();
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
        $daily->setDaysOfWeek(array(1,2,3,4,5));
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
