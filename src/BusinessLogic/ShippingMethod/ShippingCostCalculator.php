<?php

namespace Packlink\BusinessLogic\ShippingMethod;

use Logeecom\Infrastructure\Http\Exceptions\HttpBaseException;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Http\DTO\Package;
use Packlink\BusinessLogic\Http\DTO\ShippingServiceDeliveryDetails;
use Packlink\BusinessLogic\Http\DTO\ShippingServiceSearch;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;

/**
 * Class ShippingCostCalculator.
 *
 * @package Packlink\BusinessLogic\ShippingMethod
 */
class ShippingCostCalculator
{
    /**
     * Calculates shipping cost for specified shipping method and delivery parameters.
     *
     * @param ShippingMethod $shippingMethod Shipping method to calculate costs for.
     * @param string $fromCountry Departure country code.
     * @param string $fromZip Departure zip code.
     * @param string $toCountry Destination country code.
     * @param string $toZip Destination zip code.
     * @param Package[] $packages Array of packages.
     *
     * @return float Calculated shipping cost for service if found. Otherwise, 0.0;
     */
    public static function getShippingCost(
        ShippingMethod $shippingMethod,
        $fromCountry,
        $fromZip,
        $toCountry,
        $toZip,
        array $packages
    ) {
        $defaultCost = 0;
        $serviceId = $shippingMethod->getServiceId();
        $params = new ShippingServiceSearch($serviceId, $fromCountry, $fromZip, $toCountry, $toZip, $packages);

        try {
            $response = self::getProxy()->getShippingServicesDeliveryDetails($params);
            // if we don't get a response, it means this shipping method does not do delivery for specified params
            if (count($response)) {
                $defaultCost = $response[0]->basePrice;
            }
        } catch (HttpBaseException $e) {
            // If API is not available, get stored default shipping cost on method without calculation.
            $defaultCost = $shippingMethod->getDefaultPacklinkShippingCost($fromCountry, $toCountry);
        }

        return $defaultCost ? $shippingMethod->getCost($defaultCost, $packages) : 0;
    }

    /**
     * Returns shipping costs for all given shipping methods that support delivery with specified parameters.
     *
     * @param ShippingMethod[] $shippingMethods Shipping methods to do a calculation for.
     * @param string $fromCountry Departure country code.
     * @param string $fromZip Departure zip code.
     * @param string $toCountry Destination country code.
     * @param string $toZip Destination zip code.
     * @param Package[] $packages Array of packages.
     *
     * @return array Array of shipping cost per service. Key is service id and value is shipping cost.
     */
    public static function getShippingCosts(
        $shippingMethods,
        $fromCountry,
        $fromZip,
        $toCountry,
        $toZip,
        array $packages
    ) {
        if (empty($shippingMethods)) {
            return array();
        }

        $params = new ShippingServiceSearch(null, $fromCountry, $fromZip, $toCountry, $toZip, $packages);
        try {
            $response = self::getProxy()->getShippingServicesDeliveryDetails($params);

            $result = self::calculateShippingCostsPerShippingMethod($shippingMethods, $response, $packages);
        } catch (HttpBaseException $e) {
            // Fallback when API is not available.
            $result = self::getDefaultShippingCosts($shippingMethods, $fromCountry, $toCountry, $packages);
        }

        return $result;
    }

    /**
     * Prepares raw response data for shipping costs calculation.
     *
     * @param ShippingMethod[] $shippingMethods Array of active shipping methods in the system.
     *
     * @param ShippingServiceDeliveryDetails[] $shippingServiceDeliveries Array of shipping service delivery details.
     *
     * @param Package[] $packages Array of packages.
     *
     * @return array Array of shipping cost per service. Key is service id and value is shipping cost.
     */
    private static function calculateShippingCostsPerShippingMethod(
        array $shippingMethods,
        array $shippingServiceDeliveries,
        array $packages
    ) {
        $shippingCosts = array();

        foreach ($shippingMethods as $shippingMethod) {
            foreach ($shippingServiceDeliveries as $id => $shippingServiceDeliveryDetails) {
                if ($shippingMethod->getServiceId() === $shippingServiceDeliveryDetails->id) {
                    $shippingCosts[$shippingMethod->getServiceId()] = $shippingMethod->getCost(
                        $shippingServiceDeliveryDetails->basePrice,
                        $packages
                    );

                    unset($shippingServiceDeliveries[$id]);
                    break;
                }
            }
        }

        return $shippingCosts;
    }

    /**
     * Returns default shipping costs for all given shipping methods in the system.
     *
     * @param ShippingMethod[] $shippingMethods Array of shipping methods fmr the shop.
     *
     * @param string $fromCountry Departure country code.
     * @param string $toCountry Destination country code.
     * @param Package[] $packages Array of packages.
     *
     * @return array Array of shipping cost per service. Key is service id and value is shipping cost.
     */
    private static function getDefaultShippingCosts(array $shippingMethods, $fromCountry, $toCountry, array $packages)
    {
        $shippingCosts = array();

        foreach ($shippingMethods as $shippingMethod) {
            $baseCost = $shippingMethod->getDefaultPacklinkShippingCost($fromCountry, $toCountry);
            $shippingCosts[$shippingMethod->getServiceId()] = $shippingMethod->getCost($baseCost, $packages);
        }

        return $shippingCosts;
    }

    /**
     * Gets instance of proxy.
     *
     * @return \Packlink\BusinessLogic\Http\Proxy Packlink Proxy.
     */
    private static function getProxy()
    {
        /** @var Proxy $proxy */
        $proxy = ServiceRegister::getService(Proxy::CLASS_NAME);

        return $proxy;
    }
}
