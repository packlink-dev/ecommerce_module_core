<?php

namespace Packlink\DemoUI\Controllers;

class ManualRefreshServiceController extends BaseHttpController
{
    public function enqueueUpdateTask()
    {
        $controller = new \Packlink\BusinessLogic\Controllers\ManualRefreshServiceController();

        $this->output($controller->enqueueUpdateTask());
    }

    public function getTaskStatus()
    {
        $controller = new \Packlink\BusinessLogic\Controllers\ManualRefreshServiceController();

        $this->output($controller->getTaskStatus());
    }
}