<?php

namespace Packlink\BusinessLogic\Tasks;

use Logeecom\Infrastructure\TaskExecution\Exceptions\AbortTaskExecutionException;
use Logeecom\Infrastructure\TaskExecution\Interfaces\Priority;
use Logeecom\Infrastructure\TaskExecution\Task;
use Packlink\BusinessLogic\Tasks\Interfaces\BusinessTask;

class LegacyTaskAdapter implements BusinessTask
{
    /**
     * Wrapped infrastructure task.
     *
     * @var Task
     */
    private $task;

    /**
     * Task class name.
     *
     * @var string
     */
    private $taskClass;
    /**
     * Optional execution config override.
     *
     * @var TaskExecutionConfig|null
     */
    private $executionConfig;

    /**
     * LegacyTaskAdapter constructor.
     *
     * @param Task $task Infrastructure task to wrap.
     */
    public function __construct(Task $task, TaskExecutionConfig $executionConfig = null)
    {
        $this->task = $task;
        $this->taskClass = get_class($task);
        $this->executionConfig = $executionConfig;
    }

    /**
     * Execute wrapped infrastructure task.
     *
     * @return void
     *
     * @throws AbortTaskExecutionException
     */
    public function execute()
    {
        $this->task->execute();
    }

    /**
     * Serialize task to array.
     *
     * @return array Serialized task data.
     */
    public function toArray(): array
    {
        $data = [
            'task_class' => $this->taskClass,
            'task_data' => $this->task->toArray(),
        ];

        if ($this->executionConfig !== null) {
            $data['execution_config'] = $this->executionConfig->toArray();
        }


        return $data;
    }

    /**
     * Deserialize task from array.
     *
     * @param array $data Serialized task data.
     *
     * @return static Deserialized task instance.
     */
    public static function fromArray(array $data): BusinessTask
    {
        $taskClass = $data['task_class'];
        $taskData = $data['task_data'];

        /** @var Task $task */
        $task = $taskClass::fromArray($taskData);

        $executionConfig = null;
        if (!empty($data['execution_config'])) {
            $executionConfig = TaskExecutionConfig::fromArray($data['execution_config']);
        }

        return new static($task, $executionConfig);
    }

    /**
     * Get wrapped infrastructure task.
     *
     * @return Task Infrastructure task.
     */
    public function getWrappedTask(): Task
    {
        return $this->task;
    }

    /**
     * Get priority (if task has it).
     *
     * @return int Priority.
     */
    public function getPriority(): int
    {
        if (method_exists($this->task, 'getPriority')) {
            return $this->task->getPriority();
        }

        return Priority::NORMAL;
    }

    /**
     * Returns wrapped task type for QueueItem.
     *
     * @return string
     */
    public function getTaskType(): string
    {
        return $this->task->getType();
    }

    /**
     * @inheritDoc
     */
    public function getExecutionConfig()
    {
        return $this->executionConfig;
    }
}
