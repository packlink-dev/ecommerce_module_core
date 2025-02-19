<?php

namespace Packlink\DemoUI\Controllers;

use Logeecom\Infrastructure\Exceptions\BaseException;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Packlink\BusinessLogic\Controllers\ShippingMethodController;

class ManualRefreshController extends BaseHttpController
{
    public function enqueueUpdateTask()
    {
        $controller = new \Packlink\BusinessLogic\Controllers\ManualRefreshController();

        $this->output($controller->enqueueUpdateTask()->toArray());
    }

    public function getTaskStatus()
    {
        $controller = new ShippingMethodController();

        sleep(2);

        if (count($controller->getAll()) > 0) {
            $this->output(array('status' => QueueItem::COMPLETED, 'message' => 'Queue item completed'));

            return;
        }

        $controller = new \Packlink\BusinessLogic\Controllers\ManualRefreshController();
        $this->output($controller->getTaskStatus()->toArray());

        try {
            $status = $controller->getTaskStatus();
        } catch (BaseException $e) {
            $status = QueueItem::FAILED;
        }

        $this->output(array('status' => $status));
    }
}