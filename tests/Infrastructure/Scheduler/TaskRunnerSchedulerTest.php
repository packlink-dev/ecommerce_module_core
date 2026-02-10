<?php

namespace Logeecom\Tests\Infrastructure\Scheduler;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\Scheduler\Models\DailySchedule;
use Logeecom\Infrastructure\Scheduler\Models\HourlySchedule;
use Logeecom\Infrastructure\Scheduler\Models\Schedule;
use Logeecom\Infrastructure\Scheduler\Models\WeeklySchedule;
use Logeecom\Infrastructure\Scheduler\TaskRunnerScheduler;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\AsyncProcessUrlProviderInterface;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerConfigInterface;
use Logeecom\Infrastructure\TaskExecution\TaskRunnerConfig;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\FooBusinessTask;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\FooTask;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestAsyncProcessUrlProvider;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestShopConfiguration;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\OrderShipmentDetails\Models\OrderShipmentDetails;
use Packlink\BusinessLogic\Scheduler\DTO\ScheduleConfig;
use PHPUnit\Framework\TestCase;

class TaskRunnerSchedulerTest extends TestCase
{
    /** @var TestShopConfiguration */
    private $configService;

    /** @var TaskRunnerConfigInterface */
    private $taskRunnerConfig;

    /** @var TaskRunnerScheduler */
    private $scheduler;

    /**
     * @before
     *
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function before()
    {
        $this->setUp();

        RepositoryRegistry::registerRepository(OrderShipmentDetails::CLASS_NAME, MemoryRepository::getClassName());

        $this->configService = TestServiceRegister::getService(Configuration::CLASS_NAME);

        $this->configService->setContext('test-context');

        TestServiceRegister::registerService(
            AsyncProcessUrlProviderInterface::CLASS_NAME,
            function () {
                return new TestAsyncProcessUrlProvider();
            }
        );

        TestServiceRegister::registerService(
            TaskRunnerConfigInterface::CLASS_NAME,
            function () {
                $config = ServiceRegister::getService(Configuration::CLASS_NAME);
                $urlProvider = ServiceRegister::getService(AsyncProcessUrlProviderInterface::CLASS_NAME);

                return new TaskRunnerConfig($config, $urlProvider);
            }
        );

        $this->taskRunnerConfig = ServiceRegister::getService(TaskRunnerConfigInterface::CLASS_NAME);

        // 3) Sistem pod testom
        $this->scheduler = new TaskRunnerScheduler($this->configService, $this->taskRunnerConfig);
    }

    public function testScheduleWeeklyCreatesAndPersistsWeeklySchedule()
    {
        $cfg = new ScheduleConfig(3, 10, 15, true);

        $this->scheduler->scheduleWeekly(new FooBusinessTask(), $cfg);

        $saved = $this->getLastSavedSchedule();
        $this->assertInstanceOf(WeeklySchedule::class, $saved);

        $arr = $this->scheduleToArray($saved);

        // Queue name dolazi iz TaskRunnerConfig->getSchedulerQueueName().
        $this->assertSame('SchedulerCheckTaskQueue', $arr['queueName']);
        $this->assertSame('test-context', $arr['context']);
        $this->assertEquals(3, $arr['day']);
        $this->assertEquals(10, $arr['hour']);
        $this->assertEquals(15, $arr['minute']);
    }

    public function testScheduleDailyFallsBackToDayOfWeekWhenDaysOfWeekEmpty()
    {
        $cfg = new ScheduleConfig(3, 10, 15, true);

        $cfg->setDaysOfWeek([]); // empty
        $cfg->setDayOfWeek(5);   // should fallback to [5]
        $cfg->setHour(9);
        $cfg->setMinute(30);
        $cfg->setRecurring(false);

        $this->scheduler->scheduleDaily(new FooBusinessTask(), $cfg);

        $saved = $this->getLastSavedSchedule();
        $this->assertInstanceOf(DailySchedule::class, $saved);

        $arr = $this->scheduleToArray($saved);
        $this->assertSame('SchedulerCheckTaskQueue', $arr['queueName']);
        $this->assertSame('test-context', $arr['context']);
        $this->assertEquals(9, $arr['hour']);
        $this->assertEquals(30, $arr['minute']);

        // critical: daysOfWeek becomes [5]
        $this->assertEquals([5], $arr['daysOfWeek']);
    }

    public function testScheduleHourlySetsOptionalFieldsAndPersists()
    {
        $cfg = new ScheduleConfig(3, 10, 15, true);
        $cfg->setDayOfWeek(2);
        $cfg->setMinute(5);

        $cfg->setStartHour(8);
        $cfg->setStartMinute(10);
        $cfg->setEndHour(18);
        $cfg->setEndMinute(50);
        $cfg->setInterval(2);
        $cfg->setRecurring(true);

        $this->scheduler->scheduleHourly(new FooBusinessTask(), $cfg);

        $saved = $this->getLastSavedSchedule();
        $this->assertInstanceOf(HourlySchedule::class, $saved);

        $arr = $this->scheduleToArray($saved);
        $this->assertSame('SchedulerCheckTaskQueue', $arr['queueName']);
        $this->assertSame('test-context', $arr['context']);

        $this->assertEquals(2, $arr['day']);
        $this->assertEquals(5, $arr['minute']);
        $this->assertEquals(8, $arr['startHour']);
        $this->assertEquals(10, $arr['startMinute']);
        $this->assertEquals(18, $arr['endHour']);
        $this->assertEquals(50, $arr['endMinute']);
        $this->assertEquals(2, $arr['interval']);
        $this->assertEquals(true, $arr['recurring']);
    }

    private function getLastSavedSchedule()
    {
        $all = $this->getSavedSchedules();
        $this->assertNotEmpty($all, 'No schedules saved.');
        return $all[count($all) - 1];
    }

    /**
     * @return Schedule[]
     */
    private function getSavedSchedules()
    {
        $repo = RepositoryRegistry::getRepository(Schedule::CLASS_NAME);
        $all = $repo->select();

        return $all ?: [];
    }

    /**
     * Converts schedule to array in a robust way.
     * Prefer toArray() (most infra entities have it); otherwise fallback to getters if needed.
     */
    private function scheduleToArray($schedule)
    {
        if (method_exists($schedule, 'toArray')) {
            return $schedule->toArray();
        }

        // Minimal fallback if your models don't have toArray
        $arr = [];
        foreach (['queueName','context','day','hour','minute','daysOfWeek','startHour','startMinute','endHour','endMinute','interval','recurring'] as $field) {
            $getter = 'get' . ucfirst($field);
            $isGetter = 'is' . ucfirst($field);
            if (method_exists($schedule, $getter)) {
                $arr[$field] = $schedule->{$getter}();
            } elseif (method_exists($schedule, $isGetter)) {
                $arr[$field] = $schedule->{$isGetter}();
            }
        }

        return $arr;
    }

}