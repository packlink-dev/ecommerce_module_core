<?php

namespace Packlink\BusinessLogic\Controllers;

use Exception;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Location\LocationService;

/**
 * Class LocationsController
 *
 * @package Packlink\BusinessLogic\Controllers
 */
class LocationsController
{
    /**
     * Location service.
     *
     * @var \Packlink\BusinessLogic\Location\LocationService
     */
    protected $service;

    /**
     * LocationsController constructor.
     */
    public function __construct()
    {
        $this->service = ServiceRegister::getService(LocationService::CLASS_NAME);
    }

    /**
     * Retrieves locations.
     *
     * @param array $query Associative array in the following format ['query' => '...', 'country' => '...'].
     *
     * @return \Packlink\BusinessLogic\Http\DTO\LocationInfo[]
     */
    public function searchLocations(array $query)
    {
        if (empty($query['query']) || empty($query['country'])) {
            return array();
        }

        try {
            $result = $this->service->searchLocations($query['country'], $query['query']);
        } catch (Exception $e) {
            Logger::logError('Location search failed.', 'Core', $e->getTrace());

            $result = array();
        }

        return $result;
    }
}