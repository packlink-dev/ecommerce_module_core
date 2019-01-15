<?php

namespace Logeecom\Tests\BusinessLogic\Scheduler;

use Logeecom\Infrastructure\Utility\TimeProvider;
use Logeecom\Tests\Common\TestComponents\Utility\TestTimeProvider;
use Logeecom\Tests\Common\TestServiceRegister;
use Packlink\BusinessLogic\Scheduler\Models\YearlySchedule;
use PHPUnit\Framework\TestCase;

/**
 * Class YearlyScheduleTest
 * @package Logeecom\Tests\BusinessLogic\Scheduler
 */
class YearlyScheduleTest extends TestCase
{
    /**
     * Current date time
     * @var \DateTime
     */
    private $nowTime;
    /**
     * Yearly schedule instance
     * @var \Packlink\BusinessLogic\Scheduler\Models\WeeklySchedule
     */
    protected $yearlySchedule;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();

        // Always return 2018-03-21 13:42:05
        $this->yearlySchedule = new YearlySchedule();
        $this->yearlySchedule->setMonth(7);
        $this->yearlySchedule->setDay(24);
        $this->yearlySchedule->setHour(13);
        $this->yearlySchedule->setMinute(45);

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
    public function testNextScheduleThisYear()
    {
        $expected = new \DateTime();
        $expected->setTimezone(new \DateTimeZone('UTC'));
        $expected->setDate(2018, 7, 24);
        $expected->setTime(13, 45);

        $nextSchedule = $this->yearlySchedule->calculateNextSchedule();
        $this->assertEquals($expected->getTimestamp(), $nextSchedule->getTimestamp());
    }

    /**
     * @throws \Exception Throws this exception when unable to create DateTime object
     */
    public function testNextScheduleOnNexYear()
    {
        $this->yearlySchedule->setMonth(1);
        $expected = new \DateTime();
        $expected->setTimezone(new \DateTimeZone('UTC'));
        $expected->setDate(2019, 1, 24);
        $expected->setTime(13, 45);

        $nextSchedule = $this->yearlySchedule->calculateNextSchedule();
        $this->assertEquals($expected->getTimestamp(), $nextSchedule->getTimestamp());
    }
}
