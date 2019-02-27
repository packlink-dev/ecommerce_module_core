<?php

namespace Packlink\BusinessLogic\Location;

use Logeecom\Infrastructure\Http\Exceptions\HttpBaseException;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\BaseService;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Http\DTO\BaseDto;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\ShippingMethod\ShippingCostCalculator;
use Packlink\BusinessLogic\ShippingMethod\ShippingMethodService;

/**
 * Class LocationService
 *
 * @package Packlink\BusinessLogic\Location
 */
class LocationService extends BaseService
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;
    /**
     * Configuration instance.
     *
     * @var Configuration
     */
    private $configuration;
    /**
     * Shipping method service.
     *
     * @var ShippingMethodService
     */
    private $shippingMethodService;
    /**
     * Proxy instance.
     *
     * @var Proxy
     */
    private $proxy;

    /**
     * LocationService constructor.
     */
    protected function __construct()
    {
        parent::__construct();

        $this->proxy = ServiceRegister::getService(Proxy::CLASS_NAME);
        $this->shippingMethodService = ServiceRegister::getService(ShippingMethodService::CLASS_NAME);
        $this->configuration = ServiceRegister::getService(Configuration::CLASS_NAME);
    }

    /**
     * Returns array of locations for this shipping method.
     *
     * @param int $shippingMethodId Shipping method identifier.
     * @param string $toCountry Country code to where package is shipped.
     * @param string $toPostCode Post code to where package is shipped.
     *
     * @return array Locations.
     */
    public function getLocations($shippingMethodId, $toCountry, $toPostCode)
    {
        $warehouse = $this->configuration->getDefaultWarehouse();
        $method = $this->shippingMethodService->getShippingMethod($shippingMethodId);
        if ($warehouse === null || $method === null) {
            return array();
        }

        try {
            $cheapestService = ShippingCostCalculator::getCheapestShippingService(
                $method,
                $warehouse->country,
                $warehouse->postalCode,
                $toCountry,
                $toPostCode
            );

            $locations = $this->proxy->getLocations($cheapestService->serviceId, $toCountry, $toPostCode);

            return $this->transformCollectionToResponse($locations);
        } catch (\InvalidArgumentException $e) {
            return array();
        } catch (HttpBaseException $e) {
            return array();
        }
    }

    /**
     * Transforms collection of DTOs to an array response.
     *
     * @param BaseDto[] $collection
     *
     * @return array
     */
    protected function transformCollectionToResponse($collection)
    {
        $result = array();

        foreach ($collection as $element) {
            $result[] = $element->toArray();
        }

        return $result;
    }
}
