<?php

namespace Packlink\BusinessLogic\Controllers;

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Controllers\DTO\DashboardStatus;
use Packlink\BusinessLogic\ShippingMethod\ShippingMethodService;

/**
 * Class DashboardController
 * @package Packlink\BusinessLogic\Controllers
 */
class DashboardController
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Shipping method service.
     *
     * @var ShippingMethodService
     */
    private $shippingMethodService;
    /**
     * Configuration instance.
     *
     * @var Configuration
     */
    private $configuration;

    /**
     * DashboardController constructor.
     */
    public function __construct()
    {
        $this->configuration = ServiceRegister::getService(Configuration::CLASS_NAME);
        $this->shippingMethodService = ServiceRegister::getService(ShippingMethodService::CLASS_NAME);
    }

    /**
     * Returns Dashboard status object with configuration flags.
     *
     * @return DashboardStatus Dashboard status.
     */
    public function getStatus()
    {
        $dashboardDto = new DashboardStatus();
        $dashboardDto->isParcelSet = (bool)$this->configuration->getDefaultParcel();
        $dashboardDto->isWarehouseSet = (bool)$this->configuration->getDefaultWarehouse();
        $dashboardDto->isShippingMethodSet = $this->shippingMethodService->isAnyMethodActive();

        return $dashboardDto;
    }
}
