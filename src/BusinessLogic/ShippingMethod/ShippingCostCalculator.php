<?php

namespace Packlink\BusinessLogic\ShippingMethod;

use Logeecom\Infrastructure\Http\Exceptions\HttpBaseException;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Http\DTO\Package;
use Packlink\BusinessLogic\Http\DTO\ShippingServiceDetails;
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
        $data = self::getShippingCosts(array($shippingMethod), $fromCountry, $fromZip, $toCountry, $toZip, $packages);
        $data = !empty($data) ? current($data) : 0;

        return $data;
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

        $result = array();
        $params = new ShippingServiceSearch(null, $fromCountry, $fromZip, $toCountry, $toZip, $packages);
        try {
            $response = self::getProxy()->getShippingServicesDeliveryDetails($params);

            $result = self::calculateShippingCostsPerShippingMethod($shippingMethods, $response, $packages);
        } catch (HttpBaseException $e) {
            // Fallback when API is not available.
            if ($e->getCode() !== 400) {
                $result = self::getDefaultShippingCosts($shippingMethods, $fromCountry, $toCountry, $packages);
            }
        }

        return $result;
    }

    /**
     * @param \Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod $shippingMethod
     * @param array $packages
     * @param string $fromCountry
     * @param string $toCountry
     * @param int $serviceId
     * @param float $basePrice
     *
     * @return float Calculated cost.
     */
    protected static function calculateShippingMethodCost(
        ShippingMethod $shippingMethod,
        array $packages,
        $serviceId = 0,
        $basePrice = 0.0,
        $fromCountry = '',
        $toCountry = ''
    ) {
        $cost = PHP_INT_MAX;
        foreach ($shippingMethod->getShippingServices() as $methodService) {
            if (($serviceId !== 0 && $methodService->serviceId === $serviceId)
                || ($methodService->departureCountry === $fromCountry
                    && $methodService->destinationCountry === $toCountry)
            ) {
                $baseCost = self::calculateCostForShippingMethod(
                    $shippingMethod,
                    $basePrice ?: $methodService->basePrice,
                    $packages
                );

                $cost = min($cost, $baseCost);
            }
        }

        return $cost !== PHP_INT_MAX ? $cost : 0.0;
    }

    /**
     * Prepares raw response data for shipping costs calculation.
     *
     * @param ShippingMethod[] $shippingMethods Array of active shipping methods in the system.
     * @param ShippingServiceDetails[] $shippingServices Array of shipping services delivery details.
     * @param Package[] $packages Array of packages.
     *
     * @return array Array of shipping cost per service. Key is service id and value is shipping cost.
     */
    private static function calculateShippingCostsPerShippingMethod(
        array $shippingMethods,
        array $shippingServices,
        array $packages
    ) {
        $shippingCosts = array();

        /** @var ShippingMethod $method */
        foreach ($shippingMethods as $method) {
            $cost = PHP_INT_MAX;
            foreach ($shippingServices as $service) {
                $baseCost = self::calculateShippingMethodCost($method, $packages, $service->id, $service->basePrice);
                if ($baseCost > 0) {
                    $cost = min($cost, $baseCost);
                }
            }

            if ($cost !== PHP_INT_MAX) {
                $shippingCosts[$method->getId()] = $cost;
            }
        }

        return $shippingCosts;
    }

    /**
     * Calculates shipping cost for given shipping method based on its pricing policy.
     *
     * @param ShippingMethod $shippingMethod Method to calculate cost for.
     * @param float $baseCost Base cost from Packlink API or from default cost.
     * @param Package[] $packages Array of packages.
     *
     * @return float Calculated shipping cost.
     */
    protected static function calculateCostForShippingMethod(
        ShippingMethod $shippingMethod,
        $baseCost,
        array $packages = array()
    ) {
        if ($shippingMethod->getPricingPolicy() === ShippingMethod::PRICING_POLICY_FIXED) {
            return round(self::calculateFixedPriceCost($shippingMethod, $packages), 2);
        }

        return round(self::calculateVariableCost($shippingMethod, $baseCost), 2);
    }

    /**
     * Calculates shipping cost for fixed price policy.
     *
     * @param ShippingMethod $shippingMethod Method to calculate cost for.
     * @param Package[] $packages Array of packages.
     *
     * @return float Calculated fixed price cost.
     */
    protected static function calculateFixedPriceCost(ShippingMethod $shippingMethod, array $packages)
    {
        $totalWeight = 0;
        foreach ($packages as $package) {
            $totalWeight += $package->weight;
        }

        $fixedPricePolicies = $shippingMethod->getFixedPricePolicy();
        foreach ($fixedPricePolicies as $fixedPricePolicy) {
            if ($fixedPricePolicy->from <= $totalWeight && $fixedPricePolicy->to > $totalWeight) {
                return $fixedPricePolicy->amount;
            }
        }

        return $fixedPricePolicies[count($fixedPricePolicies) - 1]->amount;
    }

    /**
     * Calculates cost based on default value and percent or Packlink pricing policy.
     *
     * @param ShippingMethod $shippingMethod Method to calculate cost for.
     * @param float $defaultCost Base cost on which to apply pricing policy.
     *
     * @return float Final cost.
     */
    protected static function calculateVariableCost(ShippingMethod $shippingMethod, $defaultCost)
    {
        $pricingPolicy = $shippingMethod->getPricingPolicy();
        if ($pricingPolicy === ShippingMethod::PRICING_POLICY_PACKLINK) {
            return $defaultCost;
        }

        $policy = $shippingMethod->getPercentPricePolicy();
        $amount = $defaultCost * ($policy->amount / 100);
        if ($policy->increase) {
            return $defaultCost + $amount;
        }

        return $defaultCost - $amount;
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
    protected static function getDefaultShippingCosts(array $shippingMethods, $fromCountry, $toCountry, array $packages)
    {
        $shippingCosts = array();

        /** @var ShippingMethod $shippingMethod */
        foreach ($shippingMethods as $shippingMethod) {
            $cost = self::calculateShippingMethodCost($shippingMethod, $packages, 0, 0.0, $fromCountry, $toCountry);

            if ($cost > 0) {
                $shippingCosts[$shippingMethod->getId()] = $cost;
            }
        }

        return $shippingCosts;
    }

    /**
     * Returns default packlink shipping cost for given shipping method.
     *
     * @param ShippingMethod $shippingMethod Method to get default cost for.
     * @param string $fromCountry Departure country code.
     * @param string $toCountry Destination country code.
     *
     * @return float|int Default shipping cost.
     */
    protected static function getDefaultPacklinkShippingCost(ShippingMethod $shippingMethod, $fromCountry, $toCountry)
    {
        foreach ($shippingMethod->getShippingServices() as $shippingCost) {
            if ($shippingCost->departureCountry === $fromCountry
                && $shippingCost->destinationCountry === $toCountry
            ) {
                return $shippingCost->basePrice;
            }
        }

        return 0;
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
