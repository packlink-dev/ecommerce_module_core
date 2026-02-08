<?php

namespace Packlink\BusinessLogic\Tasks\Interfaces;

use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerConfigInterface;
use Packlink\BusinessLogic\Tasks\TaskExecutionConfig;

/**
 * Business Task interface.
 *
 * Defines the contract for pure business tasks.
*/
interface BusinessTask
{
    /**
     * Execute business logic.
     *
     * @return \Generator|void
     *
     * @throws \Exception If execution fails.
     */
    public function execute();

    /**
     * Serialize task to array.
     *
     * Used by task executors to store task data.
     *
     * @return array Task data.
     */
    public function toArray(): array;

    /**
     * Deserialize task from array.
     *
     * Used by task executors to reconstruct task.
     *
     * @param array $data Task data.
     *
     * @return static Task instance.
     */
    public static function fromArray(array $data): BusinessTask;

    /**
     * Return execution config for a task
     *
     * @return TaskExecutionConfig|null
     */
    public function getExecutionConfig();
}