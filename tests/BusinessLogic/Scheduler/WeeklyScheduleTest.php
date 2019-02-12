<?php

namespace Logeecom\Tests\BusinessLogic\Scheduler;

use Logeecom\Infrastructure\Utility\TimeProvider;
use Logeecom\Tests\Infrastructure\Common\TestComponents\Utility\TestTimeProvider;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
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
     * @var string
     */
    private $oldTimeZone;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();

        $this->oldTimeZone = date_default_timezone_get();
        date_default_timezone_set('UTC');

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
        $this->setCurrentDateTime($nowDateTime);
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
     * @throws \Exception Throws this exception when unable to create DateTime object
     */
    public function testNextScheduleOnSpecificWeekDayNext()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $expected = new \DateTime();
        $expected->setTimezone(new \DateTimeZone('UTC'));
        $expected->setDate(2018, 3, 26);
        $expected->setTime(3, 0);

        $this->weeklySchedule->setNextSchedule();
        $nextSchedule = $this->weeklySchedule->getNextSchedule();
        $this->assertEquals($expected->getTimestamp(), $nextSchedule->getTimestamp());
    }

    /**
     * @throws \Exception
     */
    public function testNextScheduleOnDifferentDays()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $expected = new \DateTime();
        $expected->setTimezone(new \DateTimeZone('UTC'));
        $expected->setDate(2018, 3, 26);
        $expected->setTime(3, 0);

        $this->setMonday();
        $this->weeklySchedule->setNextSchedule();
        $nextSchedule = $this->weeklySchedule->getNextSchedule();
        $this->assertEquals($expected->getTimestamp(), $nextSchedule->getTimestamp());

        $this->setWednesday();
        $this->weeklySchedule->setNextSchedule();
        $nextSchedule = $this->weeklySchedule->getNextSchedule();
        $this->assertEquals($expected->getTimestamp(), $nextSchedule->getTimestamp());

        $this->setSunday();
        $this->weeklySchedule->setNextSchedule();
        $nextSchedule = $this->weeklySchedule->getNextSchedule();
        $this->assertEquals($expected->getTimestamp(), $nextSchedule->getTimestamp());
    }

    /**
     * @throws \Exception
     */
    private function setMonday()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $nowDateTime = new \DateTime();
        $nowDateTime->setTimezone(new \DateTimeZone('UTC'));
        $nowDateTime->setDate(2018, 3, 19); // Monday
        $nowDateTime->setTime(13, 42, 5);

        $this->setCurrentDateTime($nowDateTime);
    }

    /**
     * @throws \Exception
     */
    private function setWednesday()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $nowDateTime = new \DateTime();
        $nowDateTime->setTimezone(new \DateTimeZone('UTC'));
        $nowDateTime->setDate(2018, 3, 21); // Wednesday
        $nowDateTime->setTime(11, 30, 5);

        $this->setCurrentDateTime($nowDateTime);
    }

    /**
     * @throws \Exception
     */
    private function setSunday()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $nowDateTime = new \DateTime();
        $nowDateTime->setTimezone(new \DateTimeZone('UTC'));
        $nowDateTime->setDate(2018, 3, 25); // Sunday
        $nowDateTime->setTime(11, 30, 5);

        $this->setCurrentDateTime($nowDateTime);
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

        $this->weeklySchedule->setNextSchedule();
        $nextSchedule = $this->weeklySchedule->getNextSchedule();
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
        $expected->setDate(2018, 3, 30);
        $expected->setTime(3, 0);

        $this->weeklySchedule->setNextSchedule();
        $nextSchedule = $this->weeklySchedule->getNextSchedule();
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
        $expected->setDate(2018, 4, 27);
        $expected->setTime(3, 0);

        $this->weeklySchedule->setNextSchedule();
        $nextSchedule = $this->weeklySchedule->getNextSchedule();
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

        $this->weeklySchedule->setNextSchedule();
        $nextSchedule = $this->weeklySchedule->getNextSchedule();
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

        $this->weeklySchedule->setNextSchedule();
        $nextSchedule = $this->weeklySchedule->getNextSchedule();
        $this->assertEquals($expected->getTimestamp(), $nextSchedule->getTimestamp());
    }

    /**
     * Sets current date and time for testing purposes.
     *
     * @param \DateTime $dateTime
     *
     * @throws \Exception
     */
    private function setCurrentDateTime(\DateTime $dateTime)
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $timeProvider = new TestTimeProvider();
        $timeProvider->setCurrentLocalTime($dateTime);
        $this->nowTime = $dateTime;

        new TestServiceRegister(
            array(
                TimeProvider::CLASS_NAME => function () use ($timeProvider) {
                    return $timeProvider;
                },
            )
        );
    }
}
