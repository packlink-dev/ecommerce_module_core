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
 * Class ShippingCostCalculator
 *
 * @package Packlink\BusinessLogic\ShippingMethod
 */
class ShippingCostCalculator
{
    /**
     * @var ShippingMethodService
     */
    private static $shippingMethodService;

    /**
     * Calculates shipping cost for specified parameters.
     *
     * @param int $serviceId ID of service to calculate costs for.
     * @param string $fromCountry Departure country code.
     * @param string $fromZip Departure zip code.
     * @param string $toCountry Destination country code.
     * @param string $toZip Destination zip code.
     * @param Package[] $packages Array of parcels.
     *
     * @return float Calculated shipping cost for service if found. Otherwise, 0.0;
     */
    public static function getShippingCost($serviceId, $fromCountry, $fromZip, $toCountry, $toZip, array $packages)
    {
        /** @var Proxy $proxy */
        $proxy = ServiceRegister::getService(Proxy::CLASS_NAME);

        $defaultCost = 0;
        $shippingMethod = self::getShippingMethodService()->getShippingMethodForService($serviceId);
        if ($shippingMethod === null) {
            return $defaultCost;
        }

        if ($shippingMethod->getPricingPolicy() === ShippingMethod::PRICING_POLICY_FIXED) {
            return $shippingMethod->getCost(0, $fromCountry, $toCountry, $packages);
        }

        try {
            $response = $proxy->getShippingServicesDeliveryDetails(
                self::getCostSearchParameters($fromCountry, $fromZip, $toCountry, $toZip, $packages, $serviceId)
            );
            if (count($response)) {
                $defaultCost = $response[0]->basePrice;
            }
        } catch (HttpBaseException $e) {
            // Fallback when API is not available.
            $defaultCost = $shippingMethod->getDefaultShippingCost($fromCountry, $toCountry);
        }

        return $defaultCost ? $shippingMethod->getCost($defaultCost) : 0;
    }

    /**
     * Returns shipping costs for all available shipping services that support specified parameters.
     *
     * @param string $fromCountry Departure country code.
     * @param string $fromZip Departure zip code.
     * @param string $toCountry Destination country code.
     * @param string $toZip Destination zip code.
     * @param Package[] $packages Array of parcels.
     *
     * @return array Key-value pairs representing shipping method identifiers and their corresponding shipping costs.
     */
    public static function getShippingCosts($fromCountry, $fromZip, $toCountry, $toZip, array $packages)
    {
        $activeMethods = self::getShippingMethodService()->getActiveMethods();
        if (empty($activeMethods)) {
            return array();
        }

        try {
            $response = self::getShippingCostsFromProxy($fromCountry, $fromZip, $toCountry, $toZip, $packages);
            $shippingCosts = self::prepareDataForShippingCostsCalculation($response, $activeMethods);
        } catch (HttpBaseException $e) {
            // Fallback when API is not available.
            $shippingCosts = self::getDefaultShippingCosts($fromCountry, $toCountry, $activeMethods);
        }

        $calculatedShippingCosts = self::calculateShippingCosts($fromCountry, $toCountry, $packages, $shippingCosts);

        return $calculatedShippingCosts;
    }

    /**
     * Retrieves API response for all available shipping delivery methods
     * and prepares it for shipping costs calculation.
     *
     * @param string $fromCountry Departure country code.
     * @param string $fromZip Departure zip code.
     * @param string $toCountry Destination country code.
     * @param string $toZip Destination zip code.
     * @param Package[] $packages Array of parcels.
     *
     * @return array Array of prepared data for shipping costs calculation.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    private static function getShippingCostsFromProxy($fromCountry, $fromZip, $toCountry, $toZip, array $packages)
    {
        /** @var Proxy $proxy */
        $proxy = ServiceRegister::getService(Proxy::CLASS_NAME);

        $response = $proxy->getShippingServicesDeliveryDetails(
            self::getCostSearchParameters($fromCountry, $fromZip, $toCountry, $toZip, $packages)
        );

        return $response;
    }

    /**
     * Prepares raw response data for shipping costs calculation.
     *
     * @param ShippingServiceDeliveryDetails[] $shippingServiceDeliveries Array of shipping service delivery details.
     * @param ShippingMethod[] $activeMethods Array of active shipping methods in the system.
     *
     * @return array Array of prepared data for shipping cost calculation.
     */
    private static function prepareDataForShippingCostsCalculation(
        array $shippingServiceDeliveries,
        array $activeMethods
    ) {
        $shippingCosts = array();
        $activeMethodServiceIds = array_map(
            function ($activeMethod) {
                /** @var ShippingMethod $activeMethod */
                return $activeMethod->getServiceId();
            },
            $activeMethods
        );

        foreach ($shippingServiceDeliveries as $shippingServiceDeliveryDetails) {
            if (!in_array($shippingServiceDeliveryDetails->id, $activeMethodServiceIds, true)) {
                continue;
            }

            $shippingMethod = self::getShippingMethodService()
                ->getShippingMethodForService($shippingServiceDeliveryDetails->id);
            if ($shippingMethod === null) {
                continue;
            }

            if ($shippingMethod->getPricingPolicy() === ShippingMethod::PRICING_POLICY_FIXED) {
                $shippingCosts[$shippingMethod->getServiceId()] = 0;
            } else {
                $shippingCosts[$shippingMethod->getServiceId()] = $shippingServiceDeliveryDetails->basePrice;
            }
        }

        return $shippingCosts;
    }

    /**
     * Transforms parameters to proper DTO.
     *
     * @param string $fromCountry Departure country code.
     * @param string $fromZip Departure zip code.
     * @param string $toCountry Destination country code.
     * @param string $toZip Destination zip code.
     * @param Package[] $packages Parcel info.
     * @param int $serviceId ID of service to calculate costs for.
     *
     * @return ShippingServiceSearch Resulting object
     */
    private static function getCostSearchParameters(
        $fromCountry,
        $fromZip,
        $toCountry,
        $toZip,
        array $packages,
        $serviceId = null
    ) {
        $params = new ShippingServiceSearch();

        if ($serviceId !== null) {
            $params->serviceId = $serviceId;
        }

        $params->fromCountry = $fromCountry;
        $params->fromZip = $fromZip;
        $params->toCountry = $toCountry;
        $params->toZip = $toZip;
        $params->packages = $packages;

        return $params;
    }

    /**
     * Calculates shipping costs based on their pricing policy.
     *
     * @param string $fromCountry Departure country code.
     * @param string $toCountry Destination country code.
     * @param Package[] $packages Array of parcels.
     * @param array $shippingCosts Array of prepared data that needs to be calculated.
     *
     * @return array Calculated shipping costs.
     */
    private static function calculateShippingCosts($fromCountry, $toCountry, array $packages, array $shippingCosts)
    {
        $calculatedShippingCosts = array();

        foreach ($shippingCosts as $serviceId => $shippingCost) {
            $shippingMethod = self::getShippingMethodService()->getShippingMethodForService($serviceId);
            if ($shippingMethod === null) {
                continue;
            }

            $calculatedShippingCosts[$shippingMethod->getServiceId()] = $shippingMethod->getCost(
                $shippingCost,
                $fromCountry,
                $toCountry,
                $packages
            );
        }

        return $calculatedShippingCosts;
    }

    /**
     * Returns default shipping costs for all active shipping methods in the system.
     *
     * @param string $fromCountry Departure country code.
     * @param string $toCountry Destination country code.
     * @param ShippingMethod[] $activeMethods Array of active shipping methods in the system.
     *
     * @return array Prepared data of default shipping costs for
     */
    private static function getDefaultShippingCosts($fromCountry, $toCountry, array $activeMethods)
    {
        $shippingCosts = array();

        foreach ($activeMethods as $shippingMethod) {
            $shippingCosts[$shippingMethod->getServiceId()] = $shippingMethod->getDefaultShippingCost(
                $fromCountry,
                $toCountry
            );
        }

        return $shippingCosts;
    }

    /**
     * Returns shipping method service.
     *
     * @return ShippingMethodService
     */
    private static function getShippingMethodService()
    {
        if (self::$shippingMethodService === null) {
            self::$shippingMethodService = ServiceRegister::getService(ShippingMethodService::CLASS_NAME);
        }

        return self::$shippingMethodService;
    }
}
