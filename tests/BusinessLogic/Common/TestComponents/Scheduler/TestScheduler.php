<?php

namespace Logeecom\Tests\BusinessLogic\Common\TestComponents\Scheduler;

use Packlink\BusinessLogic\Scheduler\Interfaces\SchedulerInterface;

class TestScheduler implements SchedulerInterface
{
    public $weeklyCalls = array();
    public $dailyCalls = array();
    public $hourlyCalls = array();

    public function scheduleWeekly(callable $callback, int $dayOfWeek, int $hour, int $minute): void
    {
        $this->weeklyCalls[] = array(
            'callback' => $callback,
            'dayOfWeek' => $dayOfWeek,
            'hour' => $hour,
            'minute' => $minute,
        );
    }

    public function scheduleDaily(callable $callback, int $dayOfWeek, int $hour, int $minute): void
    {
        $this->dailyCalls[] = array(
            'callback' => $callback,
            'dayOfWeek' => $dayOfWeek,
            'hour' => $hour,
            'minute' => $minute,
        );
    }

    public function scheduleHourly(callable $callback, int $dayOfWeek, int $hour, int $minute): void
    {
        $this->hourlyCalls[] = array(
            'callback' => $callback,
            'dayOfWeek' => $dayOfWeek,
            'hour' => $hour,
            'minute' => $minute,
        );
    }
}
