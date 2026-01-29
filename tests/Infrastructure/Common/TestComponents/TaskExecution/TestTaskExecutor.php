<?php

namespace Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution;

use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskExecutorInterface;
use Packlink\BusinessLogic\Tasks\Interfaces\BusinessTask;

class TestTaskExecutor implements TaskExecutorInterface
{
    /**
     * @var BusinessTask[]
     */
    public $enqueuedTasks = array();

    /**
     * @var array[] Each item: ['task' => BusinessTask, 'delaySeconds' => int]
     */
    public $scheduledTasks = array();

    public function enqueue(BusinessTask $businessTask)
    {
        $this->enqueuedTasks[] = $businessTask;
    }

    public function scheduleDelayed(BusinessTask $businessTask, int $delaySeconds)
    {
        $this->scheduledTasks[] = array(
            'task' => $businessTask,
            'delaySeconds' => $delaySeconds,
        );
    }
}
