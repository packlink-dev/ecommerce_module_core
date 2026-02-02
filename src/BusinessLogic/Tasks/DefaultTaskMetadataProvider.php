<?php

namespace Packlink\BusinessLogic\Tasks;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\TaskExecution\Interfaces\Priority;
use Packlink\BusinessLogic\Tasks\Interfaces\BusinessTask;
use Packlink\BusinessLogic\Tasks\Interfaces\TaskMetadataProviderInterface;

class DefaultTaskMetadataProvider implements TaskMetadataProviderInterface
{
    /**
     * @var Configuration
     */
    private $config;

    /**
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * Get execution configuration for a business task.
     *
     * @param BusinessTask $task Business task.
     *
     * @return TaskExecutionConfig Execution configuration.
     */
    public function getExecutionConfig(BusinessTask $task): TaskExecutionConfig
    {
        if (method_exists($task, 'getExecutionConfig')) {
            $executionConfig = $task->getExecutionConfig();
            if ($executionConfig instanceof TaskExecutionConfig) {
                return $executionConfig;
            }
        }

        $queueName = $this->config->getDefaultQueueName();
        $priority = $this->getTaskPriority($task);
        $context = $this->config->getContext();

        return new TaskExecutionConfig($queueName, $priority, $context);
    }

    /**
     * Get priority for task.
     *
     * If task provides getPriority(), use it, otherwise fallback to Priority::NORMAL.
     *
     * @param BusinessTask $task
     *
     * @return int
     */
    protected function getTaskPriority(BusinessTask $task): int
    {
        if (method_exists($task, 'getPriority')) {
            $priority = $task->getPriority();
            if ($priority !== null) {
                return $priority;
            }
        }

        return Priority::NORMAL;
    }
}
