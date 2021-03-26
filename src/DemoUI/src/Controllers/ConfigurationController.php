<?php

namespace Packlink\DemoUI\Controllers;

/**
 * Class ConfigurationController.
 *
 * @package Packlink\BusinessLogic\Controllers
 */
class ConfigurationController extends BaseHttpController
{
    /**
     * Prepares data for configuration page.
     */
    public function getData()
    {
        $ctrl = new \Packlink\BusinessLogic\Controllers\ConfigurationController();
        echo json_encode(
            array(
                'helpUrl' => $ctrl->getHelpLink(),
                'version' => $this->getConfigService()->getModuleVersion(),
            )
        );
    }
}
