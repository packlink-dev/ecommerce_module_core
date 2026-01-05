<?php

namespace Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution;

use Logeecom\Infrastructure\TaskExecution\TaskRunnerWakeupService;

class TestTaskRunnerWakeupService extends TaskRunnerWakeupService
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
        // Track the wakeup attempt BEFORE calling parent
        // This allows us to count ALL wakeup attempts (including those blocked by GUID)
        $this->callHistory['wakeup'][] = array('timestamp' => time());

        // Call parent to execute actual GUID locking logic
        parent::wakeup();
    }
}