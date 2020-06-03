<?php


namespace Packlink\DemoUI\Controllers;

/**
 * Class ModuleStateController
 * @package Packlink\DemoUI\Controllers
 */
class ModuleStateController
{
    /**
     * Gets current app state.
     */
    public function getCurrentState()
    {
        $controller = new \Packlink\BusinessLogic\Controllers\ModuleStateController();

        echo json_encode($controller->getCurrentState()->toArray());
    }
}