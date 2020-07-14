<?php


namespace Packlink\DemoUI\Controllers;

/**
 * Class OnboardingController
 *
 * @package Packlink\DemoUI\Controllers
 */
class OnboardingController
{
    /**
     * Gets current app state.
     */
    public function getCurrentState()
    {
        $controller = new \Packlink\BusinessLogic\Controllers\OnboardingController();

        echo json_encode($controller->getCurrentState()->toArray());
    }
}
