<?php

namespace Packlink\BusinessLogic\Controllers;

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
    protected $service;

    /**
     * @param WarehouseServiceInterface $service
     *
     * WarehouseController constructor.
     */
    public function __construct(WarehouseServiceInterface $service)
    {
        $this->service = $service;
    }

    /**
     * Provides warehouse data.
     *
     * @return Warehouse | null
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
}