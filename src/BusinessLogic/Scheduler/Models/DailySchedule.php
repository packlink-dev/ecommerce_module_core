<?php

namespace Packlink\BusinessLogic\Scheduler\Models;

/**
 * Class DailySchedule.
 *
 * @package Logeecom\Infrastructure\Scheduler\Models
 */
class DailySchedule extends Schedule
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
    protected $fields = array('id', 'queueName', 'minute', 'hour', 'day', 'month', 'daysOfWeek', 'context');
    /**
     * Week day numbers, starting from Monday to Sunday.
     *
     * @var int[]
     */
    protected $daysOfWeek = array(1, 2, 3, 4, 5, 6, 7);

    /**
     * Returns week days on which task should be scheduled.
     *
     * @return int[] Array of week days.
     */
    public function getDaysOfWeek()
    {
        return $this->daysOfWeek;
    }

    /**
     * Sets week days on which task should be scheduled.
     *
     * @param int[] $daysOfWeek Array of week days.
     */
    public function setDaysOfWeek(array $daysOfWeek)
    {
        $this->daysOfWeek = $daysOfWeek;
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
        $dayOfWeek = (int)date('N', $now->getTimestamp());

        $shouldStartAt = $this->now();
        $shouldStartAt->setTimestamp($now->getTimestamp());
        $shouldStartAt->setTime($this->getHour(), $this->getMinute());
        $shouldStartAtTs = $shouldStartAt->getTimestamp();
        if (in_array($dayOfWeek, $this->daysOfWeek) && $now->getTimestamp() <= $shouldStartAtTs) {
            return $shouldStartAt;
        }

        $interval = new \DateInterval('P1D');
        do {
            $shouldStartAt->add($interval);
            $dayOfWeek = (int)date('N', $shouldStartAt->getTimestamp());
        } while (!in_array($dayOfWeek, $this->getDaysOfWeek()));

        return $shouldStartAt;
    }
}
