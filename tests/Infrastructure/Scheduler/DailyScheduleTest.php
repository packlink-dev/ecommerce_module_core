<?php

namespace Logeecom\Tests\Infrastructure\Scheduler;

use Logeecom\Infrastructure\Utility\TimeProvider;
use Logeecom\Tests\Infrastructure\Common\TestComponents\Utility\TestTimeProvider;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Logeecom\Infrastructure\Scheduler\Models\DailySchedule;
use PHPUnit\Framework\TestCase;

/**
 * Class DailyScheduleTest
 * @package Logeecom\Tests\Infrastructure\Scheduler
 */
class DailyScheduleTest extends TestCase
{
    /**
     * Daily schedule instance
     * @var \Logeecom\Infrastructure\Scheduler\Models\DailySchedule
     */
    public $dailySchedule;

    /**
     * @before
     *
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function before()
    {
        $this->setUp();

        // Always return 2018-03-21 13:42:05
        $this->dailySchedule = new DailySchedule();
        $this->dailySchedule->setHour(15);
        $this->dailySchedule->setMinute(0);

        /** @noinspection PhpUnhandledExceptionInspection */
        $nowDateTime = new \DateTime();
        $nowDateTime->setDate(2018, 3, 21);
        $nowDateTime->setTime(13, 42, 5);

        /** @noinspection PhpUnhandledExceptionInspection */
        $timeProvider = new TestTimeProvider();
        $timeProvider->setCurrentLocalTime($nowDateTime);

        new TestServiceRegister(
            array(
                TimeProvider::CLASS_NAME => function () use ($timeProvider) {
                    return $timeProvider;
                },
            )
        );
    }

    /**
     * @throws \Exception Throws this exception when unable to create DateTime object
     */
    public function testNextScheduleSameDay()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $expected = new \DateTime();
        $expected->setTimezone(new \DateTimeZone('UTC'));
        $expected->setDate(2018, 3, 21);
        $expected->setTime(15, 0);

        $this->dailySchedule->setNextSchedule();
        $nextSchedule = $this->dailySchedule->getNextSchedule();
        $this->assertEquals($expected->getTimestamp(), $nextSchedule->getTimestamp());
    }

    /**
     * @throws \Exception Throws this exception when unable to create DateTime object
     */
    public function testNextScheduleNextDay()
    {
        $this->dailySchedule->setHour(11);
        /** @noinspection PhpUnhandledExceptionInspection */
        $expected = new \DateTime();
        $expected->setTimezone(new \DateTimeZone('UTC'));
        $expected->setDate(2018, 3, 22);
        $expected->setTime(11, 0);

        $this->dailySchedule->setNextSchedule();
        $nextSchedule = $this->dailySchedule->getNextSchedule();
        $this->assertEquals($expected->getTimestamp(), $nextSchedule->getTimestamp());
    }

    /**
     * @throws \Exception Throws this exception when unable to create DateTime object
     */
    public function testNextScheduleOnSpecificWeekDay()
    {
        // Monday and Friday
        $this->dailySchedule->setDaysOfWeek(array(1, 5));
        /** @noinspection PhpUnhandledExceptionInspection */
        $expected = new \DateTime();
        $expected->setTimezone(new \DateTimeZone('UTC'));
        $expected->setDate(2018, 3, 23);
        $expected->setTime(15, 0);

        $this->dailySchedule->setNextSchedule();
        $nextSchedule = $this->dailySchedule->getNextSchedule();
        $this->assertEquals($expected->getTimestamp(), $nextSchedule->getTimestamp());
    }

    /**
     * @throws \Exception Throws this exception when unable to create DateTime object
     */
    public function testNextScheduleOnSpecificWeekDayNext()
    {
        // Monday
        $this->dailySchedule->setDaysOfWeek(array(1));
        /** @noinspection PhpUnhandledExceptionInspection */
        $expected = new \DateTime();
        $expected->setTimezone(new \DateTimeZone('UTC'));
        $expected->setDate(2018, 3, 26);
        $expected->setTime(15, 0);

        $this->dailySchedule->setNextSchedule();
        $nextSchedule = $this->dailySchedule->getNextSchedule();
        $this->assertEquals($expected->getTimestamp(), $nextSchedule->getTimestamp());
    }
}
