<?php

namespace Logeecom\Infrastructure\TaskExecution\Interfaces;

use Packlink\BusinessLogic\Tasks\Interfaces\BusinessTask;

interface TaskExecutorInterface
{
    /**
     * Fully qualified name of this interface.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * Enqueue task for immediate execution.
     *
     * Task will be executed asynchronously as soon as possible.
     *
     * @param BusinessTask $businessTask Business task instance (e.g., SendDraftBusinessTask).
     *
     * @return void
     */
    public function enqueue(BusinessTask $businessTask);

    /**
     * Schedule task for delayed execution.
     *
     * Task will be executed asynchronously after specified delay.
     *
     * @param BusinessTask $businessTask Business task instance.
     * @param int $delaySeconds Delay in seconds before execution.
     *
     * @return void
     */
    public function scheduleDelayed(BusinessTask $businessTask, int $delaySeconds);
}