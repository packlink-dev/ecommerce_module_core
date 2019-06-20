<?php

namespace Logeecom\Tests\Infrastructure\Common\TestComponents\Utility\Events;

use Logeecom\Infrastructure\Utility\Events\Event;
use Logeecom\Infrastructure\Utility\Events\EventEmitter;

class TestEventEmitter extends EventEmitter
{
    public function fire(Event $event)
    {
        parent::fire($event);
    }
}