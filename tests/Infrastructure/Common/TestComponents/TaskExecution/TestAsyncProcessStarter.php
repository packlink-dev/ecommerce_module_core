<?php

namespace Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution;

use Logeecom\Infrastructure\TaskExecution\Interfaces\AsyncProcessService;
use Logeecom\Infrastructure\TaskExecution\Interfaces\Runnable;

class TestAsyncProcessStarter implements AsyncProcessService
{
    /**
     * @var bool
     */
    private $doStartRunner;
    private $callHistory = array();

    public function __construct($doStartRunner = false)
    {
        $this->doStartRunner = $doStartRunner;
    }

    public function getMethodCallHistory($methodName)
    {
        return !empty($this->callHistory[$methodName]) ? $this->callHistory[$methodName] : array();
    }

    public function start(Runnable $runner)
    {
        $this->callHistory['start'][] = array('runner' => $runner);
        if ($this->doStartRunner) {
            $runner->run();
        }
    }

    /**
     * @param bool $doStartRunner
     */
    public function setDoStartRunner($doStartRunner)
    {
        $this->doStartRunner = $doStartRunner;
    }

    /**
     * @inheritDoc
     */
    public function runProcess($guid)
    {
    }
}