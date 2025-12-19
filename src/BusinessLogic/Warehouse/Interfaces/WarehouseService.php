<?php

namespace Packlink\BusinessLogic\Warehouse\Interfaces;

use Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException;
use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException;
use Packlink\BusinessLogic\Warehouse\Warehouse;

interface WarehouseService
{
    const CLASS_NAME = __CLASS__;

    /**
     * Gets a warehouse.
     *
     * @param bool $createIfNotExist Indicates whether to create a new object if the default does not exist.
     *
     * @return Warehouse|null
     */
    public function getWarehouse($createIfNotExist = true);

    /**
     * Updates warehouse data.
     *
     * @param array $payload
     *
     * @return Warehouse
     *
     * @throws QueueStorageUnavailableException
     * @throws FrontDtoNotRegisteredException
     * @throws FrontDtoValidationException
     */
    public function updateWarehouseData($payload);
}