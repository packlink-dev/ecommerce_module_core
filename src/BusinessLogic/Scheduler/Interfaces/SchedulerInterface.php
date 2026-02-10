<?php

namespace Packlink\BusinessLogic\Scheduler\Interfaces;

use Packlink\BusinessLogic\Scheduler\DTO\ScheduleConfig;
use Packlink\BusinessLogic\Tasks\Interfaces\BusinessTask;

interface SchedulerInterface
{
    const CLASS_NAME = __CLASS__;

    /**
     * @param BusinessTask $businessTask
     * @param ScheduleConfig $config
     */
    public function scheduleWeekly(BusinessTask $businessTask, ScheduleConfig $config);

    /**
     * @param BusinessTask $businessTask
     * @param ScheduleConfig $config
     */
    public function scheduleDaily(BusinessTask $businessTask, ScheduleConfig $config);

    /**
     * @param BusinessTask $businessTask
     * @param ScheduleConfig $config
     */
    public function scheduleHourly(BusinessTask $businessTask, ScheduleConfig $config);
}
