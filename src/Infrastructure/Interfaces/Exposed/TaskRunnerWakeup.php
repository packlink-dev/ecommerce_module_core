<?php

namespace Logeecom\Infrastructure\Interfaces\Exposed;

/**
 * Interface TaskRunnerWakeup.
 *
 * @package Logeecom\Infrastructure\Interfaces\Exposed
 */
interface TaskRunnerWakeup
{
    /**
     * Fully qualified name of this interface.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * Wakes up TaskRunner instance asynchronously if active instance is not already running.
     */
    public function wakeup();
}
