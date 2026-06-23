<?php

namespace Packlink\DemoUI\Controllers;

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Controllers\AutoConfigurationController;
use Packlink\BusinessLogic\UpdateShippingServices\Interfaces\UpdateShippingServicesOrchestratorInterface;
use Packlink\BusinessLogic\UpdateShippingServices\Interfaces\UpdateShippingServiceTaskStatusServiceInterface;

/**
 * Class AutoConfigureController.
 *
 * @package Packlink\DemoUI\Controllers
 */
class AutoConfigureController extends BaseHttpController
{
    /**
     * Starts the auto-configuration process.
     */
    public function start()
    {
        /** @var UpdateShippingServicesOrchestratorInterface $orchestrator */
        $orchestrator = ServiceRegister::getService(UpdateShippingServicesOrchestratorInterface::class);
        /** @var UpdateShippingServiceTaskStatusServiceInterface $statusService */
        $statusService = ServiceRegister::getService(UpdateShippingServiceTaskStatusServiceInterface::class);

        $controller = new AutoConfigurationController($orchestrator, $statusService);

        $this->output(array('success' => $controller->start(true)));
    }
}