<?php

namespace Logeecom\Infrastructure\Scheduler\Interfaces;

interface SchedulerCheckPolicyInterface
{
    /**
     * Fully qualified name of this interface.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * Indicates whether the schedule check task should be enqueued.
     *
     * @return bool
     */
    public function shouldEnqueue();
}
