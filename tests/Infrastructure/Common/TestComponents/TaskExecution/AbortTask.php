<?php

namespace Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution;

use Logeecom\Infrastructure\TaskExecution\Exceptions\AbortTaskExecutionException;
use Logeecom\Infrastructure\TaskExecution\Task;

/**
 * Class AbortTask.
 *
 * @package Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution
 */
class AbortTask extends Task
{
    public function execute()
    {
        throw new AbortTaskExecutionException('Abort mission!');
    }

    /**
     * @inheritDoc
     */
    public static function fromArray(array $array)
    {
        return new static();
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array();
    }

    /**
     * @inheritDoc
     */
    public function __serialize()
    {
        return $this->toArray();
    }

    /**
     * @inheritDoc
     */
    public function __unserialize($data)
    {
    }
}
