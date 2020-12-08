<?php

namespace Packlink\BusinessLogic\Controllers;

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Country\Country;
use Packlink\BusinessLogic\Country\WarehouseCountryService;
use Packlink\BusinessLogic\Warehouse\WarehouseService;

/**
 * Class WarehouseController
 *
 * @package Packlink\BusinessLogic\Controllers
 */
class WarehouseController
{
    /**
     * Warehouse service.
     *
     * @var \Packlink\BusinessLogic\Warehouse\WarehouseService
     */
    protected $service;

    /**
     * WarehouseController constructor.
     */
    public function __construct()
    {
        $this->service = ServiceRegister::getService(WarehouseService::CLASS_NAME);
    }

    /**
     * Provides warehouse data.
     *
     * @return \Packlink\BusinessLogic\Warehouse\Warehouse | null
     */
    public function getWarehouse()
    {
        return $this->service->getWarehouse();
    }

    /**
     * Updates warehouse.
     *
     * @param array $data
     *
     * @return \Packlink\BusinessLogic\Warehouse\Warehouse
     *
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function updateWarehouse(array $data)
    {
        return $this->service->updateWarehouseData($data);
    }

    /**
     * Returns available countries for warehouse location.
     *
     * @return Country[]
     */
    public function getWarehouseCountries()
    {
        /** @var WarehouseCountryService $countryService */
        $countryService = ServiceRegister::getService(WarehouseCountryService::CLASS_NAME);

        return $countryService->getSupportedCountries(false);
    }
}