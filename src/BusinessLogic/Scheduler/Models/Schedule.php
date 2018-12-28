<?php

namespace Packlink\BusinessLogic\Scheduler\Models;

use Logeecom\Infrastructure\ORM\Configuration\EntityConfiguration;
use Logeecom\Infrastructure\ORM\Configuration\Indexes\DateTimeIndex;
use Logeecom\Infrastructure\ORM\Configuration\IndexMap;
use Logeecom\Infrastructure\ORM\Entities\Entity;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Task;
use Logeecom\Infrastructure\Utility\TimeProvider;

/** @noinspection PhpDocMissingThrowsInspection */

/**
 * Class Schedule
 * @package Logeecom\Infrastructure\Scheduler\Models
 */
class Schedule extends Entity
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Date and time of next schedule
     *
     * @var \DateTime
     */
    public $nextSchedule;
    /**
     * Queue name where task should be queued to
     *
     * @var string
     */
    protected $queueName;
    /**
     * Schedule minute
     *
     * @var int
     */
    protected $minute = 0;
    /**
     * Schedule hour
     *
     * @var int
     */
    protected $hour = 0;
    /**
     * Schedule day
     *
     * @var int
     */
    protected $day = 1;
    /**
     * Schedule month
     *
     * @var int
     */
    protected $month = 1;
    /**
     * Task that is to be queued for execution
     *
     * @var Task
     */
    protected $task;

    /**
     * Schedule constructor.
     *
     * @param Task $task Task that is to be queued for execution
     * @param string $queueName Queue name in which task should be queued into
     */
    public function __construct(Task $task = null, $queueName = null)
    {
        $this->task = $task;
        $this->queueName = $queueName;
    }

    /**
     * Calculates next schedule time
     *
     * @return \DateTime Next schedule date
     */
    public function calculateNextSchedule()
    {
        return $this->now();
    }

    /**
     * Returns entity configuration object
     *
     * @return EntityConfiguration Entity configuration with index
     */
    public function getConfig()
    {
        $map = new IndexMap();
        $map->addIndex(new DateTimeIndex('nextSchedule'));

        return new EntityConfiguration($map, 'Schedule');
    }

    /**
     * Returns task
     *
     * @return Task Task for schedule
     */
    public function getTask()
    {
        return $this->task;
    }

    /**
     * Returns queue name
     *
     * @return string Queue name
     */
    public function getQueueName()
    {
        return $this->queueName;
    }

    /**
     * Sets queue name
     *
     * @param string $queueName Queue name in which task is scheduled
     */
    public function setQueueName($queueName)
    {
        $this->queueName = $queueName;
    }

    /**
     * Returns next schedule date
     *
     * @return \DateTime Next schedule
     */
    public function getNextSchedule()
    {
        return $this->nextSchedule;
    }

    /**
     * Sets next schedule date
     *
     * @param \DateTime $nextSchedule
     */
    public function setNextSchedule(\DateTime $nextSchedule)
    {
        $this->nextSchedule = $nextSchedule;
    }

    /**
     * Returns schedule minute
     *
     * @return int Schedule minute
     */
    public function getMinute()
    {
        return $this->minute;
    }

    /**
     * Sets schedule minute
     *
     * @param int $minute Schedule minute
     */
    public function setMinute($minute)
    {
        $this->minute = $minute;
    }

    /**
     * Returns schedule hour
     *
     * @return int Schedule hour
     */
    public function getHour()
    {
        return $this->hour;
    }

    /**
     * Sets schedule hour
     *
     * @param int $hour Schedule hour
     */
    public function setHour($hour)
    {
        $this->hour = $hour;
    }

    /**
     * Returns schedule day
     *
     * @return int Schedule day
     */
    public function getDay()
    {
        return $this->day;
    }

    /**
     * Returns schedule day
     *
     * @param int $day Schedule day
     */
    public function setDay($day)
    {
        $this->day = $day;
    }

    /**
     * Returns schedule month
     *
     * @return int Month number, starting from 1 for January ending with 12 for December
     */
    public function getMonth()
    {
        return $this->month;
    }

    /**
     * Sets schedule month
     *
     * @param int $month Month number, starting from 1 for January ending with 12 for December
     */
    public function setMonth($month)
    {
        $this->month = $month;
    }

    /**
     * Returns current date and time
     *
     * @return \DateTime Date and time
     */
    protected function now()
    {
        /** @var TimeProvider $timeProvider */
        $timeProvider = ServiceRegister::getService(TimeProvider::CLASS_NAME);

        return $timeProvider->getCurrentLocalTime();
    }
}
