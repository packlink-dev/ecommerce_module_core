<?php

namespace Packlink\BusinessLogic\Controllers;

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Controllers\DTO\DashboardStatus;
use Packlink\BusinessLogic\DTO\FrontDtoFactory;
use Packlink\BusinessLogic\ShippingMethod\ShippingMethodService;

/**
 * Class DashboardController.
 *
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
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function getStatus()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return FrontDtoFactory::get(
            DashboardStatus::CLASS_KEY,
            array(
                'isParcelSet' => $this->configuration->getDefaultParcel() !== null,
                'isWarehouseSet' => $this->configuration->getDefaultWarehouse() !== null,
                'isShippingMethodSet' => $this->shippingMethodService->isAnyMethodActive(),
            )
        );
    }
}
