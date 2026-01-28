<?php

namespace Packlink\BusinessLogic\Scheduler\Interfaces;

use Packlink\BusinessLogic\Scheduler\DTO\ScheduleConfig;

interface SchedulerInterface
{
    const CLASS_NAME = __CLASS__;

    /**
     * @param callable $callback
     * @param ScheduleConfig $config
     */
    public function scheduleWeekly(callable $callback, ScheduleConfig $config);

    /**
     * @param callable $callback
     * @param ScheduleConfig $config
     */
    public function scheduleDaily(callable $callback, ScheduleConfig $config);

    /**
     * @param callable $callback
     * @param ScheduleConfig $config
     */
    public function scheduleHourly(callable $callback, ScheduleConfig $config);
}
