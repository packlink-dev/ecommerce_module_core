<?php

namespace Packlink\BusinessLogic\Location;

use InvalidArgumentException;
use Logeecom\Infrastructure\Http\Exceptions\HttpBaseException;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\BaseService;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Http\DTO\Package;
use Packlink\BusinessLogic\Http\DTO\ParcelInfo;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\Location\Exceptions\PlatformCountryNotSupportedException;
use Packlink\BusinessLogic\PostalCode\PostalCodeTransformer;
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
     * @param array $packages Packages for which to find service.
     *
     * @return array Locations.
     */
    public function getLocations($shippingMethodId, $toCountry, $toPostCode, array $packages = array())
    {
        $warehouse = $this->configuration->getDefaultWarehouse();
        $method = $this->shippingMethodService->getShippingMethod($shippingMethodId);
        if ($warehouse === null || $method === null || !$method->isDestinationDropOff()) {
            return array();
        }

        if (empty($packages)) {
            $parcel = $this->configuration->getDefaultParcel() ?: ParcelInfo::defaultParcel();
            $packages = array(new Package($parcel->weight, $parcel->width, $parcel->height, $parcel->length));
        }

        $result = array();
        try {
            $cheapestService = ShippingCostCalculator::getCheapestShippingService(
                $method,
                $warehouse->country,
                $warehouse->postalCode,
                $toCountry,
                $toPostCode,
                $packages
            );

            if ($cheapestService === null) {
                return array();
            }

            $locations = $this->proxy->getLocations(
                $cheapestService->serviceId,
                $toCountry,
                PostalCodeTransformer::transform($toCountry, $toPostCode)
            );

            $result = $this->transformCollectionToResponse($locations);
        } catch (InvalidArgumentException $e) {
        } catch (HttpBaseException $e) {
        }

        return $result;
    }

    /**
     * Performs search for locations.
     *
     * @param string $country Country code to search in.
     * @param string $query Query to search for.
     *
     * @return \Packlink\BusinessLogic\Http\DTO\LocationInfo[]
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     *
     * @throws \Packlink\BusinessLogic\Location\Exceptions\PlatformCountryNotSupportedException
     */
    public function searchLocations($country, $query)
    {
        $postalZones = $this->proxy->getPostalZones($country);

        if (empty($postalZones)) {
            throw new PlatformCountryNotSupportedException('Platform country not supported');
        }

        $result = array();
        foreach ($postalZones as $postalZone) {
            $partial = $this->proxy->searchLocations($country, $postalZone->id, $query);

            if (!empty($partial)) {
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $result = array_merge($result, $partial);
            }
        }

        return $result;
    }

    /**
     * Transforms collection of DTOs to an array response.
     *
     * @param \Logeecom\Infrastructure\Data\DataTransferObject[] $collection
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
