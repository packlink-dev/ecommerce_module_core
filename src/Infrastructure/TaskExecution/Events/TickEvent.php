<?php

namespace Logeecom\Infrastructure\TaskExecution\Events;

use Logeecom\Infrastructure\Utility\Events\Event;

/**
 * Class TickEvent.
 *
 * @package Logeecom\Infrastructure\Scheduler
 */
class TickEvent extends Event
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
}
