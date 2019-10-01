<?php

namespace Packlink\BusinessLogic\Scheduler\Models;

/**
 * Class HourlySchedule.
 *
 * @package Logeecom\Infrastructure\Scheduler\Models
 */
class HourlySchedule extends Schedule
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * Array of field names.
     *
     * @var array
     */
    protected $fields = array(
        'id',
        'recurring',
        'queueName',
        'minute',
        'hour',
        'day',
        'month',
        'startHour',
        'startMinute',
        'endHour',
        'endMinute',
        'interval',
        'context',
    );
    /**
     * Start hour of the interval.
     *
     * @var int
     */
    protected $startHour = 0;
    /**
     * Start minute of the interval.
     *
     * @var int
     */
    protected $startMinute = 0;
    /**
     * End hour of the interval.
     *
     * @var int
     */
    protected $endHour = 23;
    /**
     * End minute of the interval.
     *
     * @var int
     */
    protected $endMinute = 59;
    /**
     * Schedule interval in hours.
     *
     * @var int
     */
    protected $interval = 1;

    /**
     * Returns schedule start hour.
     *
     * @return int Interval start hour.
     */
    public function getStartHour()
    {
        return $this->startHour;
    }

    /**
     * Sets schedule start hour.
     *
     * @param int $startHour Interval start hour.
     */
    public function setStartHour($startHour)
    {
        $this->startHour = $startHour;
    }

    /**
     * Returns schedule start minute.
     *
     * @return int Interval start minute.
     */
    public function getStartMinute()
    {
        return $this->startMinute;
    }

    /**
     * Sets schedule start minute.
     *
     * @param int $startMinute Interval start minute.
     */
    public function setStartMinute($startMinute)
    {
        $this->startMinute = $startMinute;
    }

    /**
     * Returns schedule end hour.
     *
     * @return int Interval end hour.
     */
    public function getEndHour()
    {
        return $this->endHour;
    }

    /**
     * Sets schedule end hour.
     *
     * @param int $endHour Interval end hour.
     */
    public function setEndHour($endHour)
    {
        $this->endHour = $endHour;
    }

    /**
     * Returns schedule end minute.
     *
     * @return int Interval end minute.
     */
    public function getEndMinute()
    {
        return $this->endMinute;
    }

    /**
     * Sets schedule end minute.
     *
     * @param int $endMinute Interval end minute.
     */
    public function setEndMinute($endMinute)
    {
        $this->endMinute = $endMinute;
    }

    /**
     * Returns schedule interval.
     *
     * @return int Interval in hours.
     */
    public function getInterval()
    {
        return $this->interval;
    }

    /**
     * Sets schedule interval.
     *
     * @param int $interval Interval in hours.
     */
    public function setInterval($interval)
    {
        $this->interval = $interval;
    }

    /**
     * Calculates next schedule time.
     *
     * @return \DateTime Next schedule date.
     * @throws \Exception Emits Exception in case of an error while creating DateTime instance.
     */
    protected function calculateNextSchedule()
    {
        $now = $this->now();
        $nowTs = $now->getTimestamp();

        $interval = new \DateInterval("PT{$this->getInterval()}H");

        $startTime = $this->getStartTime($nowTs);
        $startTs = $startTime->getTimestamp();

        $endTime = $this->now();
        $endTime->setTimestamp($nowTs);
        $endTime->setTime($this->getEndHour(), $this->getEndMinute());
        $endTs = $endTime->getTimestamp();

        if ($nowTs <= $startTs) {
            return $startTime;
        }

        if ($nowTs === $endTs) {
            return $endTime;
        }

        while ($nowTs > $startTs) {
            if ($nowTs > $endTs || $startTs > $endTs) {
                // start from next start time of next day
                return $this->getStartTime($nowTs)->add(new \DateInterval('P1D'));
            }

            $startTime->add($interval);
            $startTs = $startTime->getTimestamp();
        }

        if ($startTs > $endTs) {
            // start from next start time of next day
            return $this->getStartTime($nowTs)->add(new \DateInterval('P1D'));
        }

        return $startTime;
    }

    /**
     * Returns start date time for the day of provided timestamp.
     *
     * @param int $nowTs Now timestamp.
     *
     * @return \DateTime Start schedule interval date and time.
     */
    private function getStartTime($nowTs)
    {
        $startTime = $this->now();
        $startTime->setTimestamp($nowTs);
        $startTime->setTime($this->getStartHour(), $this->getStartMinute() ?: $this->getMinute());

        return $startTime;
    }
}
