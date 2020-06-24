<?php


namespace Packlink\DemoUI\Controllers;

/**
 * Class OnBoardingController
 * @package Packlink\DemoUI\Controllers
 */
class OnBoardingController
{
    /**
     * Gets current app state.
     */
    public function getCurrentState()
    {
        $controller = new \Packlink\BusinessLogic\Controllers\OnBoardingController();

        echo json_encode($controller->getCurrentState()->toArray());
    }
}
