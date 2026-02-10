<?php

namespace Logeecom\Tests\BusinessLogic\Common\TestComponents\Scheduler;

use Packlink\BusinessLogic\Scheduler\DTO\ScheduleConfig;
use Packlink\BusinessLogic\Scheduler\Interfaces\SchedulerInterface;
use Packlink\BusinessLogic\Tasks\Interfaces\BusinessTask;

class TestScheduler implements SchedulerInterface
{
    public $weeklyCalls = array();
    public $dailyCalls = array();
    public $hourlyCalls = array();

    public function scheduleWeekly(BusinessTask $businessTask, ScheduleConfig $config)
    {
        $this->weeklyCalls[] = array(
            'task' => $businessTask,
            'config' => $config,
        );
    }

    public function scheduleDaily(BusinessTask $businessTask, ScheduleConfig $config)
    {
        $this->dailyCalls[] = array(
            'task' => $businessTask,
            'config' => $config,
        );
    }

    public function scheduleHourly(BusinessTask $businessTask, ScheduleConfig $config)
    {
        $this->hourlyCalls[] = array(
            'task' => $businessTask,
            'config' => $config,
        );
    }
}
