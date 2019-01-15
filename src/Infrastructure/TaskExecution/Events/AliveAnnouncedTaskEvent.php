<?php

namespace Logeecom\Infrastructure\TaskExecution\Events;

use Logeecom\Infrastructure\Utility\Events\Event;

/**
 * Class AliveAnnouncedTaskEvent.
 *
 * @package Logeecom\Infrastructure\TaskExecution\Events
 */
class AliveAnnouncedTaskEvent extends Event
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
}
