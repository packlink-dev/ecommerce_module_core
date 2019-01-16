<?php

namespace Logeecom\Tests\BusinessLogic\Scheduler;

use Logeecom\Infrastructure\Utility\TimeProvider;
use Logeecom\Tests\Common\TestComponents\Utility\TestTimeProvider;
use Logeecom\Tests\Common\TestServiceRegister;
use Packlink\BusinessLogic\Scheduler\Models\WeeklySchedule;
use PHPUnit\Framework\TestCase;

/**
 * Class WeeklyScheduleTest
 * @package Logeecom\Tests\BusinessLogic\Scheduler
 */
class WeeklyScheduleTest extends TestCase
{
    /**
     * Current date time
     * @var \DateTime
     */
    public $nowTime;
    /**
     * Weekly schedule instance
     * @var \Packlink\BusinessLogic\Scheduler\Models\WeeklySchedule
     */
    public $weeklySchedule;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();

        // Always return 2018-03-21 13:42:05
        $this->weeklySchedule = new WeeklySchedule();
        $this->weeklySchedule->setDay(1);
        $this->weeklySchedule->setHour(3);
        $this->weeklySchedule->setMinute(0);

        /** @noinspection PhpUnhandledExceptionInspection */
        $nowDateTime = new \DateTime();
        $nowDateTime->setTimezone(new \DateTimeZone('UTC'));
        $nowDateTime->setDate(2018, 3, 21);
        $nowDateTime->setTime(13, 42, 5);

        /** @noinspection PhpUnhandledExceptionInspection */
        $timeProvider = new TestTimeProvider();
        $timeProvider->setCurrentLocalTime($nowDateTime);
        $this->nowTime = $nowDateTime;

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
    public function testNextScheduleOnSpecificWeekDayNext()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $expected = new \DateTime();
        $expected->setTimezone(new \DateTimeZone('UTC'));
        $expected->setDate(2018, 3, 26);
        $expected->setTime(3, 0);

        $nextSchedule = $this->weeklySchedule->calculateNextSchedule();
        $this->assertEquals($expected->getTimestamp(), $nextSchedule->getTimestamp());
    }

    /**
     * @throws \Exception Throws this exception when unable to create DateTime object
     */
    public function testNextScheduleNextDay()
    {
        $this->weeklySchedule->setDay(4);
        $expected = new \DateTime();
        $expected->setTimezone(new \DateTimeZone('UTC'));
        $expected->setDate(2018, 3, 22);
        $expected->setTime(3, 0);

        $nextSchedule = $this->weeklySchedule->calculateNextSchedule();
        $this->assertEquals($expected->getTimestamp(), $nextSchedule->getTimestamp());
    }

    /**
     * @throws \Exception Throws this exception when unable to create DateTime object
     */
    public function testNextScheduleLastWeek()
    {
        $this->weeklySchedule->setDay(5);
        $this->weeklySchedule->setLastWeek(true);
        $expected = new \DateTime();
        $expected->setTimezone(new \DateTimeZone('UTC'));
        $expected->setDate(2018, 3, 29);
        $expected->setTime(3, 0);

        $nextSchedule = $this->weeklySchedule->calculateNextSchedule();
        $this->assertEquals($expected->getTimestamp(), $nextSchedule->getTimestamp());
    }

    /**
     * @throws \Exception Throws this exception when unable to create DateTime object
     */
    public function testNextScheduleLastWeekNextMonth()
    {
        $this->weeklySchedule->setDay(5);
        $this->nowTime->setDate(2018, 3, 31);
        $this->weeklySchedule->setLastWeek(true);
        $expected = new \DateTime();
        $expected->setTimezone(new \DateTimeZone('UTC'));
        $expected->setDate(2018, 4, 26);
        $expected->setTime(3, 0);

        $nextSchedule = $this->weeklySchedule->calculateNextSchedule();
        $this->assertEquals($expected->getTimestamp(), $nextSchedule->getTimestamp());
    }

    /**
     * @throws \Exception Throws this exception when unable to create DateTime object
     */
    public function testNextScheduleSpecificWeek()
    {
        $this->weeklySchedule->setDay(5);
        $this->weeklySchedule->setWeeks(array(51));
        $expected = new \DateTime();
        $expected->setTimezone(new \DateTimeZone('UTC'));
        $expected->setDate(2018, 12, 21);
        $expected->setTime(3, 0);

        $nextSchedule = $this->weeklySchedule->calculateNextSchedule();
        $this->assertEquals($expected->getTimestamp(), $nextSchedule->getTimestamp());
    }

    /**
     * @throws \Exception Throws this exception when unable to create DateTime object
     */
    public function testNextScheduleSpecificWeekNextYear()
    {
        $this->weeklySchedule->setDay(5);
        $this->weeklySchedule->setWeeks(array(1));
        $expected = new \DateTime();
        $expected->setTimezone(new \DateTimeZone('UTC'));
        $expected->setDate(2019, 1, 4);
        $expected->setTime(3, 0);

        $nextSchedule = $this->weeklySchedule->calculateNextSchedule();
        $this->assertEquals($expected->getTimestamp(), $nextSchedule->getTimestamp());
    }
}
