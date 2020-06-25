<?php

namespace Packlink\DemoUI\Controllers;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Warehouse\WarehouseService;
use Packlink\DemoUI\Controllers\Models\Request;
use Packlink\DemoUI\Services\BusinessLogic\ConfigurationService;

/**
 * Class DefaultWarehouseController
 *
 * @package Packlink\DemoUI\Controllers
 */
class DefaultWarehouseController
{
    /**
     * @var ConfigurationService
     */
    private $configService;

    /**
     * @param \Packlink\DemoUI\Controllers\Models\Request $request
     */
    public function getDefaultWarehouse()
    {
        /** @var WarehouseService $warehouseService */
        $warehouseService = ServiceRegister::getService(WarehouseService::CLASS_NAME);
        $warehouse = $warehouseService->getWarehouse();

        echo json_encode($warehouse ? $warehouse->toArray() : array());
    }

    /**
     * @param \Packlink\DemoUI\Controllers\Models\Request $request
     */
    public function setDefaultWarehouse(Request $request)
    {

    }

    /**
     * @param \Packlink\DemoUI\Controllers\Models\Request $request
     */
    public function getSupportedCountries(Request $request)
    {

    }

    /**
     * @param \Packlink\DemoUI\Controllers\Models\Request $request
     */
    public function searchPostalCodes(Request $request)
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