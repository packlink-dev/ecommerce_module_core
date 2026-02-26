<?php

namespace Packlink\BusinessLogic\Controllers;

use Packlink\BusinessLogic\Country\Interfaces\CountryServiceInterface;
use Packlink\BusinessLogic\Country\Models\Country;
use Packlink\BusinessLogic\Warehouse\Interfaces\WarehouseServiceInterface;
use Packlink\BusinessLogic\Warehouse\Warehouse;

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
     * @var WarehouseServiceInterface
     */
    protected $warehouseService;

    /**
     * @var CountryServiceInterface $countryService
     */
    protected $countryService;

    /**
     * @param WarehouseServiceInterface $service
     *
     * WarehouseController constructor.
     */
    public function __construct(WarehouseServiceInterface $service, CountryServiceInterface $countryService)
    {
        $this->warehouseService = $service;
        $this->countryService = $countryService;
    }

    /**
     * Provides warehouse data.
     *
     * @return Warehouse | null
     */
    public function getWarehouse()
    {
        return $this->warehouseService->getWarehouse();
    }

    /**
     * Updates warehouse.
     *
     * @param array $data
     *
     * @return \Packlink\BusinessLogic\Warehouse\Warehouse
     *
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function updateWarehouse(array $data)
    {
        return $this->warehouseService->updateWarehouseData($data);
    }

    /**
     * Returns available countries for warehouse location.
     *
     * @return Country[]
     */
    public function getWarehouseCountries(): array
    {
        return $this->countryService->getSupportedCountries(false);
    }
}