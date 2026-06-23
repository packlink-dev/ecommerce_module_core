<?php

namespace Packlink\DemoUI\Services\BusinessLogic;

use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\TaskExecutor\Interfaces\TaskExecutorInterface;
use Packlink\BusinessLogic\Tasks\Interfaces\BusinessTask;

/**
 * Class SynchronousTaskExecutor (demo).
 *
 * The core HttpTaskExecutor only persists tasks to a queue that an async process / cron
 * later runs. The demo has no such runner (and uses an in-memory queue), so queued tasks
 * never execute and carriers are never fetched. This executor runs the task immediately,
 * in-process, which is sufficient for the demo.
 *
 * Exceptions are logged and swallowed (never rethrown) so a failing background task
 * cannot break the foreground request (e.g. login).
 *
 * @package Packlink\DemoUI\Services\BusinessLogic
 */
class SynchronousTaskExecutor implements TaskExecutorInterface
{
    /**
     * Runs the task immediately.
     *
     * @param BusinessTask $businessTask
     *
     * @return void
     */
    public function enqueue(BusinessTask $businessTask)
    {
        $this->run($businessTask);
    }

    /**
     * Runs the task immediately (delay is ignored in the demo).
     *
     * @param BusinessTask $businessTask
     * @param int $delaySeconds
     *
     * @return void
     */
    public function scheduleDelayed(BusinessTask $businessTask, int $delaySeconds)
    {
        $this->run($businessTask);
    }

    /**
     * Executes the task, draining its progress generator if present.
     *
     * @param BusinessTask $businessTask
     *
     * @return void
     */
    private function run(BusinessTask $businessTask)
    {
        try {
            $result = $businessTask->execute();

            if ($result instanceof \Generator) {
                foreach ($result as $_progress) {
                    // Drain the generator so the task runs to completion.
                }
            }
        } catch (\Throwable $e) {
            Logger::logError(
                'Synchronous task execution failed: ' . get_class($businessTask) . ': ' . $e->getMessage(),
                'Core'
            );
        }
    }
}
