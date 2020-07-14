<?php

namespace Packlink\DemoUI\Controllers;

/**
 * Class OnboardingController
 *
 * @package Packlink\DemoUI\Controllers
 */
class OnboardingController extends BaseHttpController
{
    /**
     * Gets current app state.
     */
    public function getCurrentState()
    {
        $controller = new \Packlink\BusinessLogic\Controllers\OnboardingController();

        $this->output($controller->getCurrentState()->toArray());
    }
}
