<?php

namespace Packlink\BusinessLogic\Tasks\BusinessTasks;

use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerConfigInterface;
use Packlink\BusinessLogic\Tasks\Interfaces\BusinessTask;
use Packlink\BusinessLogic\Tasks\TaskExecutionConfig;

class AutoTestBusinessTask implements BusinessTask
{
    /**
     * Dummy data for the task.
     *
     * @var string
     */
    protected $data;

    /**
     * Optional execution config override.
     *
     * @var TaskExecutionConfig|null
     */
    private $executionConfig;

    /**
     * AutoTestBusinessTask constructor.
     *
     * @param string $data Dummy data.
     * @param TaskRunnerConfigInterface|null $taskRunnerConfig
     */
    public function __construct(string $data, TaskExecutionConfig $executionConfig  = null)
    {
        $this->data = $data;
        $this->executionConfig = $executionConfig ;
    }

    /**
     * Runs task logic.
     *
     * @return \Generator
     */
    public function execute(): \Generator
    {
        yield 5;
        Logger::logInfo('Auto-test task started');

        yield 50;
        Logger::logInfo('Auto-test task parameters', 'Core', array($this->data));

        yield 100;
        Logger::logInfo('Auto-test task ended');
    }

    /**
     * Serialize task to array.
     *
     * @return array Task data.
     */
    public function toArray(): array
    {
        $data = [
            'data' => $this->data,
        ];

        if ($this->executionConfig !== null) {
            $data['execution_config'] = $this->executionConfig->toArray();
        }

        return $data;
    }

    /**
     * Deserialize task from array.
     *
     * @param array $data Task data.
     *
     * @return static Task instance.
     */
    public static function fromArray(array $data): BusinessTask
    {
        $executionConfig = null;

        if (!empty($data['execution_config'])) {
            $executionConfig = TaskExecutionConfig::fromArray($data['execution_config']);
        }

        return new static($data['data'], $executionConfig);
    }

    /**
     * @return TaskExecutionConfig|null
     */
    public function getExecutionConfig()
    {
        return $this->executionConfig;
    }
}
