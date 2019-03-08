<?php

namespace Packlink\BusinessLogic\Scheduler\Models;

/**
 * Class MonthlySchedule.
 *
 * @package Logeecom\Infrastructure\Scheduler\Models
 */
class MonthlySchedule extends Schedule
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * Calculates next schedule time.
     *
     * @return \DateTime Next schedule date.
     * @throws \Exception Emits Exception in case of an error while creating DateTime instance.
     */
    protected function calculateNextSchedule()
    {
        $now = $this->now();
        $shouldExecuteOn = $this->now();

        $year = (int)date('Y', $now->getTimestamp());
        $month = (int)date('n', $now->getTimestamp());

        $shouldExecuteOn->setDate($year, $month, $this->getDay());
        $shouldExecuteOn->setTime($this->getHour(), $this->getMinute());

        // in case of 29th, 30th, 31st of the month switch to last day of the month
        $monthNext = (int)date('n', $shouldExecuteOn->getTimestamp());
        while ($monthNext !== $month) {
            $shouldExecuteOn->sub(new \DateInterval('P1D'));
            $monthNext = (int)date('n', $shouldExecuteOn->getTimestamp());
        }

        if ($now->getTimestamp() > $shouldExecuteOn->getTimestamp()) {
            // add one month
            $shouldExecuteOn->add(new \DateInterval('P1M'));
        }

        return $shouldExecuteOn;
    }
}
