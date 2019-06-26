<?php

namespace Logeecom\Infrastructure\TaskExecution\Events;

use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Infrastructure\Utility\Events\Event;

/**
 * Class BeforeQueueStatusChangeEvent.
 *
 * @package Logeecom\Infrastructure\Scheduler
 */
class BeforeQueueStatusChangeEvent extends Event
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Queue item.
     *
     * @var \Logeecom\Infrastructure\TaskExecution\QueueItem
     */
    private $queueItem;
    /**
     * Previous state of queue item.
     *
     * @var string
     */
    private $previousState;

    /**
     * TaskProgressEvent constructor.
     *
     * @param \Logeecom\Infrastructure\TaskExecution\QueueItem $queueItem Queue item with changed status.
     * @param string $previousState Previous state. MUST be one of the states defined as constants in @see QueueItem.
     */
    public function __construct(QueueItem $queueItem, $previousState)
    {
        $this->queueItem = $queueItem;
        $this->previousState = $previousState;
    }

    /**
     * Gets Queue item.
     *
     * @return \Logeecom\Infrastructure\TaskExecution\QueueItem Queue item.
     */
    public function getQueueItem()
    {
        return $this->queueItem;
    }

    /**
     * Gets previous state.
     *
     * @return string Previous state..
     */
    public function getPreviousState()
    {
        return $this->previousState;
    }
}
