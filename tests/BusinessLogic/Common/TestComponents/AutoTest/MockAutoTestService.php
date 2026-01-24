<?php

namespace Logeecom\Tests\BusinessLogic\Common\TestComponents\AutoTest;

use Logeecom\Infrastructure\AutoTest\AutoTestService;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskExecutorInterface;
use RuntimeException;

class MockAutoTestService extends AutoTestService
{
    public $callHistory = array();
    public $startAutoTestResult = 1;
    public $getAutoTestTaskStatusResult = null;
    public $shouldFail = false;
    public $failureMessage = 'Failure message.';

    public function __construct(TaskExecutorInterface $taskExecutor)
    {
        parent::__construct($taskExecutor);
    }

    public function startAutoTest()
    {
        $this->callHistory[] = 'startAutoTest';

        if ($this->shouldFail) {
            throw new RuntimeException($this->failureMessage);
        }

        return $this->startAutoTestResult;
    }

    public function stopAutoTestMode($loggerInitializerDelegate)
    {
        $this->callHistory[] = 'stopAutoTestMode';

        if ($this->shouldFail) {
            throw new RuntimeException($this->failureMessage);
        }
    }

    public function getAutoTestTaskStatus($queueItemId = 0)
    {
        $this->callHistory[] = 'getAutoTestTaskStatus';

        if ($this->shouldFail) {
            throw new RuntimeException($this->failureMessage);
        }

        return $this->getAutoTestTaskStatusResult;
    }
}
