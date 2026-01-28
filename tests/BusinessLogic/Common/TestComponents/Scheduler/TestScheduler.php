<?php

namespace Logeecom\Tests\BusinessLogic\Common\TestComponents\Scheduler;

use Packlink\BusinessLogic\Scheduler\DTO\ScheduleConfig;
use Packlink\BusinessLogic\Scheduler\Interfaces\SchedulerInterface;

class TestScheduler implements SchedulerInterface
{
    public $weeklyCalls = array();
    public $dailyCalls = array();
    public $hourlyCalls = array();

    public function scheduleWeekly(callable $callback, ScheduleConfig $config)
    {
        $this->weeklyCalls[] = array(
            'callback' => $callback,
            'config' => $config,
        );
    }

    public function scheduleDaily(callable $callback, ScheduleConfig $config)
    {
        $this->dailyCalls[] = array(
            'callback' => $callback,
            'config' => $config,
        );
    }

    public function scheduleHourly(callable $callback, ScheduleConfig $config)
    {
        $this->hourlyCalls[] = array(
            'callback' => $callback,
            'config' => $config,
        );
    }
}
