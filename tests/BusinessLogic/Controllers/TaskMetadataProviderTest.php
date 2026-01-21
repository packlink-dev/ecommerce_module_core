<?php

namespace Logeecom\Tests\BusinessLogic\Controllers;

use Logeecom\Infrastructure\TaskExecution\Interfaces\Priority;
use Packlink\BusinessLogic\Tasks\Interfaces\BusinessTask;
use Packlink\BusinessLogic\Tasks\Interfaces\TaskMetadataProviderInterface;
use Packlink\BusinessLogic\Tasks\TaskExecutionConfig;

class TaskMetadataProviderTest implements TaskMetadataProviderInterface
{
    /**
     * @var string
     */
    private $queueName;

    /**
     * @var string
     */
    private $context;

    public function __construct(string $queueName = '', $context = '')
    {
        $this->queueName = $queueName;
        $this->context = $context ?? '';
    }

    public function getExecutionConfig(BusinessTask $task): TaskExecutionConfig
    {
        $priority = method_exists($task, 'getPriority') ? $task->getPriority() : Priority::NORMAL;

        return new TaskExecutionConfig($this->queueName, $priority, $this->context);
    }
}
