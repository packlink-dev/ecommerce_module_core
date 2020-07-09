<?php

namespace Packlink\DemoUI\Controllers;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\ServiceRegister;

/**
 * Class ConfigurationController.
 *
 * @package Packlink\BusinessLogic\Controllers
 */
class ConfigurationController
{
    /**
     * Prepares data for configuration page.
     */
    public function getData()
    {
        /** @var \Packlink\BusinessLogic\Configuration $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        $ctrl = new \Packlink\BusinessLogic\Controllers\ConfigurationController();
        echo json_encode(
            array(
                'helpUrl' => $ctrl->getHelpLink(),
                'version' => $configService->getModuleVersion(),
            )
        );
    }
}
