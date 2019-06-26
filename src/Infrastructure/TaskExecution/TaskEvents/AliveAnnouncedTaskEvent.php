<?php

namespace Logeecom\Infrastructure\TaskExecution\TaskEvents;

use Logeecom\Infrastructure\Utility\Events\Event;

/**
 * Class AliveAnnouncedTaskEvent.
 *
 * @package Logeecom\Infrastructure\TaskExecution\TaskEvents
 */
class AliveAnnouncedTaskEvent extends Event
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
}
