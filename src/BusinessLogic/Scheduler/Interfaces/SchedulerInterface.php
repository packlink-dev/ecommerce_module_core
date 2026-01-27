<?php

namespace Packlink\BusinessLogic\Scheduler\Interfaces;

interface SchedulerInterface
{
    const CLASS_NAME = __CLASS__;

    /**
     * @param callable $callback
     * @param int $dayOfWeek
     * @param int $hour
     * @param int $minute
     */
    public function scheduleWeekly(callable $callback, int $dayOfWeek, int $hour, int $minute);

    /**
     * @param callable $callback
     * @param int $dayOfWeek
     * @param int $hour
     * @param int $minute
     */
    public function scheduleDaily(callable $callback, int $dayOfWeek, int $hour, int $minute);

    /**
     * @param callable $callback
     * @param int $dayOfWeek
     * @param int $hour
     * @param int $minute
     */
    public function scheduleHourly(callable $callback, int $dayOfWeek, int $hour, int $minute);
}
