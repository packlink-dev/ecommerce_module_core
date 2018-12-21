<?php

namespace Logeecom\Infrastructure\Interfaces\Required;

use Logeecom\Infrastructure\Interfaces\Exposed\Runnable;
use Logeecom\Infrastructure\TaskExecution\Exceptions\ProcessStarterSaveException;

/**
 * Interface AsyncProcessStarter.
 *
 * @package Logeecom\Infrastructure\Interfaces\Required
 */
interface AsyncProcessStarter
{
    /**
     * Fully qualified name of this interface.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * Starts given runner asynchronously (in new process/web request or similar)
     *
     * @param Runnable $runner Runner that should be started async
     *
     * @throws ProcessStarterSaveException
     */
    public function start(Runnable $runner);
}
