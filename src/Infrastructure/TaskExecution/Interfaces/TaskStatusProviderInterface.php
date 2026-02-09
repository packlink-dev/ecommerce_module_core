<?php

namespace Logeecom\Infrastructure\TaskExecution\Interfaces;

use Logeecom\Infrastructure\TaskExecution\Model\TaskStatus;

interface TaskStatusProviderInterface
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * @param string $type
     * @param string|null $context
     *
     * @return TaskStatus
     */
    public function getLatestStatus(string $type, $context = '');

    /**
     * @param string $type
     * @param string|null $context
     *
     * @return TaskStatus
     */
    public function getLatestStatusWithExpiration(string $type, $context = '');
}