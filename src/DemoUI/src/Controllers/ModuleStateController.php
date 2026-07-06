<?php

namespace Packlink\DemoUI\Controllers;

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Configuration;

/**
 * Class ModuleStateController.
 *
 * @package Packlink\DemoUI\Controllers
 */
class ModuleStateController extends BaseHttpController
{
    /**
     * @var bool
     */
    protected $requiresAuthentication = false;

    /**
     * Gets current app state.
     */
    public function getCurrentState()
    {
        $controller = new \Packlink\BusinessLogic\Controllers\ModuleStateController();

        $this->output($controller->getCurrentState()->toArray());
    }

    /**
     * Gets whether the integration is enabled or disabled (StateController.js polls this
     * after every state load to show the "integration disabled" popup when needed).
     */
    public function getIntegrationStatus()
    {
        /** @var Configuration $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);

        $this->output(array('status' => $configService->getIntegrationStatus() ?: 'ENABLED'));
    }
}