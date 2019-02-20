<?php

namespace Logeecom\Tests\BusinessLogic\Scheduler;

use Logeecom\Infrastructure\Utility\TimeProvider;
use Logeecom\Tests\Infrastructure\Common\TestComponents\Utility\TestTimeProvider;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Scheduler\Models\HourlySchedule;
use PHPUnit\Framework\TestCase;

/**
 * Class HourlyScheduleTest
 * @package Logeecom\Tests\BusinessLogic\Scheduler
 */
class HourlyScheduleTest extends TestCase
{
    /**
     * Hourly schedule instance
     * @var \Packlink\BusinessLogic\Scheduler\Models\HourlySchedule
     */
    public $hourlySchedule;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();

        // Always return 2018-03-21 13:42:05
        $this->hourlySchedule = new HourlySchedule();
        $this->hourlySchedule->setStartHour(8);
        $this->hourlySchedule->setStartMinute(15);
        $this->hourlySchedule->setEndHour(23);
        $this->hourlySchedule->setEndMinute(15);
        $this->hourlySchedule->setInterval(2);

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
    public function testNextHour()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $expected = new \DateTime();
        $expected->setTimezone(new \DateTimeZone('UTC'));
        $expected->setDate(2018, 3, 21);
        $expected->setTime(14, 15);

        $this->hourlySchedule->setNextSchedule();
        $nextSchedule = $this->hourlySchedule->getNextSchedule();
        $this->assertEquals($expected->getTimestamp(), $nextSchedule->getTimestamp());
    }

    /**
     * @throws \Exception Throws this exception when unable to create DateTime object
     */
    public function testBeyondInterval()
    {
        $this->hourlySchedule->setEndHour(14);
        $this->hourlySchedule->setEndMinute(0);

        $expected = new \DateTime();
        $expected->setTimezone(new \DateTimeZone('UTC'));
        $expected->setDate(2018, 3, 22);
        $expected->setTime(8, 15);

        $this->hourlySchedule->setNextSchedule();
        $nextSchedule = $this->hourlySchedule->getNextSchedule();
        $this->assertEquals($expected->getTimestamp(), $nextSchedule->getTimestamp());
    }

    /**
     * @throws \Exception Throws this exception when unable to create DateTime object
     */
    public function testEdgeInterval()
    {
        $this->hourlySchedule->setEndHour(14);
        $this->hourlySchedule->setEndMinute(15);

        $expected = new \DateTime();
        $expected->setTimezone(new \DateTimeZone('UTC'));
        $expected->setDate(2018, 3, 21);
        $expected->setTime(14, 15);

        $this->hourlySchedule->setNextSchedule();
        $nextSchedule = $this->hourlySchedule->getNextSchedule();
        $this->assertEquals($expected->getTimestamp(), $nextSchedule->getTimestamp());
    }

    /**
     * @throws \Exception Throws this exception when unable to create DateTime object
     */
    public function testEveryHourAtSpecificMinute()
    {
        $this->hourlySchedule->setStartMinute(0);
        $this->hourlySchedule->setMinute(27);

        $expected = new \DateTime();
        $expected->setTimezone(new \DateTimeZone('UTC'));
        $expected->setDate(2018, 3, 21);
        $expected->setTime(14, 27);

        $this->hourlySchedule->setNextSchedule();
        $nextSchedule = $this->hourlySchedule->getNextSchedule();
        $this->assertEquals($expected->getTimestamp(), $nextSchedule->getTimestamp());
    }
}
