<?php

namespace Logeecom\Tests\BusinessLogic\Scheduler;

use Logeecom\Infrastructure\Utility\TimeProvider;
use Logeecom\Tests\Common\TestComponents\Utility\TestTimeProvider;
use Logeecom\Tests\Common\TestServiceRegister;
use Packlink\BusinessLogic\Scheduler\Models\MonthlySchedule;
use PHPUnit\Framework\TestCase;

/**
 * Class MonthlyScheduleTest
 * @package Logeecom\Tests\BusinessLogic\Scheduler
 */
class MonthlyScheduleTest extends TestCase
{
    /**
     * Monthly schedule instance
     * @var \Packlink\BusinessLogic\Scheduler\Models\MonthlySchedule
     */
    public $monthlySchedule;
    /**
     * Current Date time
     * @var \DateTime
     */
    public $nowTime;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();

        // Always return 2018-03-21 13:42:05
        $this->monthlySchedule = new MonthlySchedule();
        $this->monthlySchedule->setDay(15);
        $this->monthlySchedule->setHour(3);
        $this->monthlySchedule->setMinute(0);

        /** @noinspection PhpUnhandledExceptionInspection */
        $nowDateTime = new \DateTime();
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
    public function testNextScheduleOnNextMonth()
    {
        $expected = new \DateTime();
        $expected->setTimezone(new \DateTimeZone('UTC'));
        $expected->setDate(2018, 4, 15);
        $expected->setTime(3, 0);

        $nextSchedule = $this->monthlySchedule->calculateNextSchedule();
        $this->assertEquals($expected->getTimestamp(), $nextSchedule->getTimestamp());
    }

    /**
     * @throws \Exception Throws this exception when unable to create DateTime object
     */
    public function testNextScheduleOnSameMonth()
    {
        $this->nowTime->setDate(2018, 2, 1);
        $expected = new \DateTime();
        $expected->setTimezone(new \DateTimeZone('UTC'));
        $expected->setDate(2018, 2, 15);
        $expected->setTime(3, 0);

        $nextSchedule = $this->monthlySchedule->calculateNextSchedule();
        $this->assertEquals($expected->getTimestamp(), $nextSchedule->getTimestamp());
    }

    /**
     * @throws \Exception Throws this exception when unable to create DateTime object
     */
    public function testLastDayInMonth()
    {
        $this->nowTime->setDate(2018, 2, 1);
        $this->monthlySchedule->setDay(31);

        $expected = new \DateTime();
        $expected->setTimezone(new \DateTimeZone('UTC'));
        $expected->setDate(2018, 2, 28);
        $expected->setTime(3, 0);

        $nextSchedule = $this->monthlySchedule->calculateNextSchedule();
        $this->assertEquals($expected->getTimestamp(), $nextSchedule->getTimestamp());
    }
}
