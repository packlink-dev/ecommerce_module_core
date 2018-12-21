<?php

namespace Logeecom\Tests\Common\TestComponents\TaskExecution;

use Logeecom\Infrastructure\Interfaces\Required\AsyncProcessStarter;
use Logeecom\Infrastructure\Interfaces\Exposed\Runnable;

class TestAsyncProcessStarter implements AsyncProcessStarter
{
    private $callHistory = array();

    public function getMethodCallHistory($methodName)
    {
        return !empty($this->callHistory[$methodName]) ? $this->callHistory[$methodName] : array();
    }

    public function start(Runnable $runner)
    {
        $this->callHistory['start'][] = array('runner' => $runner);
    }
}