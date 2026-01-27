<?php

namespace Packlink\BusinessLogic\Tasks\Interfaces;

use Packlink\BusinessLogic\Tasks\TaskExecutionConfig;

interface TaskMetadataProviderInterface
{
    /**
     * Name of the class
     */
    const CLASS_NAME = __CLASS__;

    /**
     * Get execution configuration for business task.
     *
     * Returns platform-agnostic configuration that can be used by:
     * - HTTP executor (queue name, priority, context)
     * - Action Scheduler (priority for action order)
     * - Cron executor (priority, grouping)
     * - Any other executor type
     *
     * @param BusinessTask $task Business task.
     *
     * @return TaskExecutionConfig Execution configuration.
     */
    public function getExecutionConfig(BusinessTask $task): TaskExecutionConfig;
}