<?php

namespace Logeecom\Infrastructure\TaskExecution\Interfaces;

interface TaskStatusProviderInterface
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * @param string $businessTaskClass FQN business task-a (npr UpdateShippingServicesBusinessTask::class)
     * @param string $context
     *
     * @return array{status:string, message:string|null}
     */
    public function getLatestStatus(string $businessTaskClass, string $context = ''): array;
}