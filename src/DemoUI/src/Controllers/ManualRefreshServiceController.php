<?php

namespace Packlink\DemoUI\Controllers;

use Logeecom\Infrastructure\Exceptions\BaseException;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Packlink\BusinessLogic\Controllers\ShippingMethodController;
use Packlink\BusinessLogic\Controllers\UpdateShippingServicesTaskStatusController;

class ManualRefreshServiceController extends BaseHttpController
{
    public function enqueueUpdateTask()
    {
        $controller = new \Packlink\BusinessLogic\Controllers\ManualRefreshServiceController();

        $this->output($controller->enqueueUpdateTask());
    }

    public function getTaskStatus()
    {
        $controller = new ShippingMethodController();

        if (count($controller->getAll()) > 0) {
            $this->output(array('status' => QueueItem::COMPLETED, 'message' => 'Queue item completed'));

            return;
        }

        $controller = new \Packlink\BusinessLogic\Controllers\ManualRefreshServiceController();
        $this->output($controller->getTaskStatus());

        try {
            $status = $controller->getTaskStatus();
        } catch (BaseException $e) {
            $status = QueueItem::FAILED;
        }

        $this->output(array('status' => $status));
    }
}