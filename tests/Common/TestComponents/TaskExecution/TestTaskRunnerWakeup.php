<?php

namespace Logeecom\Tests\Common\TestComponents\TaskExecution;

use Logeecom\Infrastructure\TaskExecution\TaskRunnerWakeup;

class TestTaskRunnerWakeup extends TaskRunnerWakeup
{

    private $callHistory = array();

    public function getMethodCallHistory($methodName)
    {
        return !empty($this->callHistory[$methodName]) ? $this->callHistory[$methodName] : array();
    }

    public function resetCallHistory()
    {
        $this->callHistory = array();
    }

    public function wakeup()
    {
        $this->callHistory['wakeup'][] = array();
    }
}