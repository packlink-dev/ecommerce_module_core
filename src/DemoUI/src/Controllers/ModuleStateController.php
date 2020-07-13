<?php

namespace Packlink\DemoUI\Controllers;

/**
 * Class ModuleStateController.
 *
 * @package Packlink\DemoUI\Controllers
 */
class ModuleStateController extends BaseHttpController
{
    /**
     * Gets current app state.
     */
    public function getCurrentState()
    {
        $controller = new \Packlink\BusinessLogic\Controllers\ModuleStateController();

        $this->output($controller->getCurrentState()->toArray());
    }
}