<?php

namespace Logeecom\Infrastructure\TaskExecution\Interfaces;

interface TaskStatusProviderInterface
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * @param string $type
     * @param string $context
     *
     * @return array{status:string, message:string|null}
     */
    public function getLatestStatus(string $type, string $context = ''): array;
}