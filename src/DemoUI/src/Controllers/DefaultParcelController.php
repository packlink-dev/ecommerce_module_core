<?php

namespace Packlink\DemoUI\Controllers;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\DemoUI\Controllers\Models\Request;
use Packlink\DemoUI\Services\BusinessLogic\ConfigurationService;

/**
 * Class DefaultParcelController
 *
 * @package Packlink\DemoUI\Controllers
 */
class DefaultParcelController
{
    /**
     * @var ConfigurationService
     */
    private $configService;

    /**
     * @param \Packlink\DemoUI\Controllers\Models\Request $request
     */
    public function getDefaultParcel()
    {
        $parcel = $this->getConfigService()->getDefaultParcel();

        echo json_encode($parcel ? $parcel->toArray() : array());
    }

    /**
     * @param \Packlink\DemoUI\Controllers\Models\Request $request
     */
    public function setDefaultParcel(Request $request)
    {

    }

    /**
     * Returns an instance of configuration service.
     *
     * @return ConfigurationService
     */
    protected function getConfigService()
    {
        if ($this->configService === null) {
            $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configService;
    }
}