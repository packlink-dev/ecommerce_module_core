<?php

namespace Packlink\DemoUI\Controllers;

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Country\CountryService;
use Packlink\BusinessLogic\Location\LocationService;
use Packlink\BusinessLogic\Warehouse\WarehouseService;
use Packlink\DemoUI\Controllers\Models\Request;

/**
 * Class DefaultWarehouseController
 *
 * @package Packlink\DemoUI\Controllers
 */
class DefaultWarehouseController
{
    /**
     * Gets the default warehouse.
     */
    public function getDefaultWarehouse()
    {
        /** @var WarehouseService $warehouseService */
        $warehouseService = ServiceRegister::getService(WarehouseService::CLASS_NAME);
        $warehouse = $warehouseService->getWarehouse();

        echo json_encode($warehouse ? $warehouse->toArray() : array());
    }

    /**
     * Sets the default warehouse.
     *
     * @param \Packlink\DemoUI\Controllers\Models\Request $request
     *
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function setDefaultWarehouse(Request $request)
    {
        /** @var WarehouseService $warehouseService */
        $warehouseService = ServiceRegister::getService(WarehouseService::CLASS_NAME);
        $warehouseService->updateWarehouseData($request->getPayload());

        $this->getDefaultWarehouse();
    }

    /**
     * Gets the list of supported countries.
     */
    public function getSupportedCountries()
    {
        /** @var CountryService $countryService */
        $countryService = ServiceRegister::getService(CountryService::CLASS_NAME);
        $supportedCountries = $countryService->getSupportedCountries();

        $this->returnDtoEntitiesResponse($supportedCountries);
    }

    /**
     * Searches for postal codes.
     *
     * @param \Packlink\DemoUI\Controllers\Models\Request $request
     */
    public function searchPostalCodes(Request $request)
    {
        $data = $request->getPayload();

        if (empty($data['query']) || empty($data['country'])) {
            die('{}');
        }

        /** @var LocationService $locationService */
        $locationService = ServiceRegister::getService(LocationService::CLASS_NAME);

        try {
            $locations = $locationService->searchLocations($data['country'], $data['query']);
            $this->returnDtoEntitiesResponse($locations);
        } catch (\Exception $e) {
            die('{}');
        }
    }

    /**
     * Echos the FrontDto array to the output.
     *
     * @param array $data
     */
    private function returnDtoEntitiesResponse(array $data)
    {
        $result = array_map(
            function ($entity) {
                return $entity->toArray();
            },
            $data
        );

        echo json_encode($result);
    }
}