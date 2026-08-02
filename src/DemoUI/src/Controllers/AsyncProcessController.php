<?php

namespace Packlink\DemoUI\Controllers;

/**
 * Class AsyncProcessController
 *
 * Handles the fire-and-forget self-ping that Infrastructure\TaskExecution\AsyncProcessStarterService
 * sends after enqueuing a task (see AsyncProcessUrlProvider::getAsyncProcessUrl()). This demo
 * overrides TaskExecutorInterface with a SynchronousTaskExecutor (see Bootstrap::init()), so by
 * the time this ping arrives the task has already run in-process; this is a no-op acknowledgement
 * so the ping does not crash with a missing-class fatal error.
 *
 * @package Packlink\DemoUI\Controllers
 */
class AsyncProcessController extends BaseHttpController
{
    /**
     * @var bool
     */
    protected $requiresAuthentication = false;

    /**
     * No-op: the task already ran synchronously by the time this ping arrives.
     */
    public function run()
    {
        $this->output(array('success' => true));
    }
}
