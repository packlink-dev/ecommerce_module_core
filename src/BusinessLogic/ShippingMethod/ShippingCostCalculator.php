<?php

namespace Packlink\BusinessLogic\ShippingMethod;

use Exception;
use InvalidArgumentException;
use Logeecom\Infrastructure\Http\Exceptions\HttpBaseException;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Http\DTO\Package;
use Packlink\BusinessLogic\Http\DTO\ShippingServiceDetails;
use Packlink\BusinessLogic\Http\DTO\ShippingServiceSearch;
use Packlink\BusinessLogic\Http\DTO\SystemInfo;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\PostalCode\PostalCodeTransformer;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingPricePolicy;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingService;
use Packlink\BusinessLogic\SystemInformation\SystemInfoService;

/**
 * Class ShippingCostCalculator.
 *
 * @package Packlink\BusinessLogic\ShippingMethod
 */
class ShippingCostCalculator
{
    /**
     * @var SystemInfo
     */
    protected static $systemInfo;

    /**
     * Calculates shipping cost for specified shipping method and delivery parameters.
     *
     * @param ShippingMethod $shippingMethod Shipping method to calculate costs for.
     * @param string $fromCountry Departure country code.
     * @param string $fromZip Departure zip code.
     * @param string $toCountry Destination country code.
     * @param string $toZip Destination zip code.
     * @param Package[] $packages Array of packages if calculation is done by weight.
     * @param float $totalPrice Total cart value if calculation is done by value
     * @param string $systemId Unique, ubiquitous system identifier that can be used to identify a system that the pricing policy belongs to.
     *
     * @return float Calculated shipping cost for service if found. Otherwise, 0.0;
     */
    public static function getShippingCost(
        ShippingMethod $shippingMethod,
        $fromCountry,
        $fromZip,
        $toCountry,
        $toZip,
        array $packages,
        $totalPrice,
        $systemId
    ) {
        $data = self::getShippingCosts(
            array($shippingMethod),
            $fromCountry,
            $fromZip,
            $toCountry,
            $toZip,
            $packages,
            $totalPrice,
            $systemId
        );
        $data = !empty($data) ? current($data) : 0;

        return $data;
    }

    /**
     * Returns shipping costs for all given shipping methods that support delivery for specified parameters.
     *
     * @param ShippingMethod[] $shippingMethods Shipping methods to do a calculation for.
     * @param string $fromCountry Departure country code.
     * @param string $fromZip Departure zip code.
     * @param string $toCountry Destination country code.
     * @param string $toZip Destination zip code.
     * @param Package[] $packages Array of packages if calculation is done by weight.
     * @param float $totalPrice Total cart value if calculation is done by value
     * @param string $systemId Unique, ubiquitous system identifier that can be used to identify a system that the pricing policy belongs to.
     *
     * @return array Array of shipping cost per service. Key is service id and value is shipping cost.
     */
    public static function getShippingCosts(
        $shippingMethods,
        $fromCountry,
        $fromZip,
        $toCountry,
        $toZip,
        array $packages,
        $totalPrice,
        $systemId
    ) {
        if (empty($shippingMethods)) {
            return array();
        }

        $result = array();
        $package = self::preparePackages($packages);
        try {
            /** @var SystemInfoService $systemInfoService */
            $systemInfoService = ServiceRegister::getService(SystemInfoService::CLASS_NAME);
            static::$systemInfo = $systemInfoService->getSystemInfo($systemId);
            $response = self::getPacklinkServices($fromCountry, $fromZip, $toCountry, $toZip, $package);

            $result = self::calculateShippingCostsPerShippingMethod(
                $shippingMethods,
                $response,
                $package->weight,
                $totalPrice,
                $systemId
            );
        } catch (HttpBaseException $e) {
            // Fallback when API is not available.
            if ($e->getCode() !== 400) {
                $result = self::getDefaultShippingCosts(
                    $shippingMethods,
                    $fromCountry,
                    $toCountry,
                    $package->weight,
                    $totalPrice,
                    $systemId
                );
            }
        }

        return $result;
    }

    /**
     * Returns cheapest service in shipping method. It does not take into consideration pricing policies.
     *
     * @param ShippingMethod $method
     * @param string $fromCountry From country code.
     * @param string $fromZip From zip code.
     * @param string $toCountry To country code.
     * @param string $toZip To zip code.
     * @param Package[] $packages Packages for which to find service.
     *
     * @return ShippingService|null The cheapest service if found; otherwise, null.
     */
    public static function getCheapestShippingService(
        ShippingMethod $method,
        $fromCountry,
        $fromZip,
        $toCountry,
        $toZip,
        array $packages
    ) {
        $package = self::preparePackages($packages);

        try {
            $services = self::getPacklinkServices($fromCountry, $fromZip, $toCountry, $toZip, $package);
        } catch (Exception $e) {
            $services = array();
        }

        $result = null;
        if (!empty($services)) {
            foreach ($services as $service) {
                $result = self::getCheapestService($method->getShippingServices(), $result, $service->id);
            }
        } else {
            // Fallback.
            $result = self::getCheapestService($method->getShippingServices(), $result, '', $toCountry);
        }

        if ($result !== null) {
            return $result;
        }

        throw new InvalidArgumentException(
            'No service is available for shipping method '
            . $method->getId() . ' for given destination country ' . $toCountry
            . ' and given packages ' . json_encode($packages)
        );
    }

    /**
     * Returns available Packlink services for the given departure and destination information.
     * Transforms destination postal code to a supported format, if the country is supported for postal code transformation.
     * In case of a 400 response, the request to Packlink API is reattempted with original destination postal code.
     *
     * @param string $fromCountry Departure country code.
     * @param string $fromZip Departure zip code.
     * @param string $toCountry Destination country code.
     * @param string $toZip Destination zip code.
     * @param Package $package
     *
     * @return array
     *
     * @throws HttpBaseException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    protected static function getPacklinkServices($fromCountry, $fromZip, $toCountry, $toZip, $package)
    {
        $transformedPostalCode = '';

        try {
            $transformedPostalCode = PostalCodeTransformer::transform($toCountry, $toZip);
            $searchParams = new ShippingServiceSearch(
                null,
                $fromCountry,
                $fromZip,
                $toCountry,
                $transformedPostalCode,
                array($package)
            );

            return self::getProxy()->getShippingServicesDeliveryDetails($searchParams);
        } catch (HttpBaseException $e) {
            if ($toZip !== $transformedPostalCode
                && $e->getCode() === 400
                && $e->getMessage() === 'Location not valid for the input data'
            ) {
                Logger::logWarning("Request with transformed postal code $transformedPostalCode for $toCountry country resulted in a unsuccessful response.");

                $searchParams = new ShippingServiceSearch(
                    null,
                    $fromCountry,
                    $fromZip,
                    $toCountry,
                    $toZip,
                    array($package)
                );

                return self::getProxy()->getShippingServicesDeliveryDetails($searchParams);
            }

            throw $e;
        } catch (InvalidArgumentException $e) {
            return array();
        }
    }

    /**
     * Returns default shipping costs for all given shipping methods in the system.
     *
     * @param ShippingMethod[] $shippingMethods Array of shipping methods fmr the shop.
     * @param string $fromCountry Departure country code.
     * @param string $toCountry Destination country code.
     * @param float $totalWeight Package total weight.
     * @param float $totalPrice Total cart value if calculation is done by value
     * @param string $systemId Unique, ubiquitous system identifier that can be used to identify a system that the pricing policy belongs to.
     *
     * @return array Array of shipping cost per service. Key is service id and value is shipping cost.
     */
    protected static function getDefaultShippingCosts(
        array $shippingMethods,
        $fromCountry,
        $toCountry,
        $totalWeight,
        $totalPrice,
        $systemId
    ) {
        $shippingCosts = array();

        foreach ($shippingMethods as $shippingMethod) {
            $cost = self::calculateShippingMethodCost(
                $shippingMethod,
                $totalWeight,
                $totalPrice,
                $systemId,
                0,
                0.0,
                $fromCountry,
                $toCountry
            );

            if ($cost !== false) {
                $shippingCosts[$shippingMethod->getId()] = $cost;
            }
        }

        return $shippingCosts;
    }

    /**
     * Prepares raw response data for shipping costs calculation.
     *
     * @param ShippingMethod[] $shippingMethods Array of active shipping methods in the system.
     * @param ShippingServiceDetails[] $shippingServices Array of shipping services delivery details.
     * @param float $totalWeight Package total weight.
     * @param float $totalPrice Total value if calculation is done by value
     * @param string $systemId Unique, ubiquitous system identifier that can be used to identify a system that the pricing policy belongs to.
     *
     * @return array Array of shipping cost per service. Key is service id and value is shipping cost.
     */
    protected static function calculateShippingCostsPerShippingMethod(
        array $shippingMethods,
        array $shippingServices,
        $totalWeight,
        $totalPrice,
        $systemId
    ) {
        $shippingCosts = array();

        foreach ($shippingMethods as $method) {
            $cost = PHP_INT_MAX;
            foreach ($shippingServices as $service) {
                $baseCost = self::calculateShippingMethodCost(
                    $method,
                    $totalWeight,
                    $totalPrice,
                    $systemId,
                    $service->id,
                    $service->basePrice,
                    '',
                    ''
                );

                if ($baseCost !== false) {
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
     * Calculates shipping method cost based on given criteria.
     *
     * @param ShippingMethod $shippingMethod
     * @param float $totalWeight
     * @param float $totalPrice
     * @param int $serviceId
     * @param float $basePrice
     * @param string $fromCountry
     * @param string $toCountry
     * @param string $systemId Unique, ubiquitous system identifier that can be used to identify a system that the pricing policy belongs to.
     *
     * @return float|bool Calculated cost or FALSE if cost cannot be calculated for the given criteria.
     */
    protected static function calculateShippingMethodCost(
        ShippingMethod $shippingMethod,
        $totalWeight,
        $totalPrice,
        $systemId,
        $serviceId = 0,
        $basePrice = 0.0,
        $fromCountry = '',
        $toCountry = ''
    ) {
        $cost = PHP_INT_MAX;

        if (static::isMisconfigurationDetected($shippingMethod)) {
            $basePrice = static::getMisconfigurationFixedPrice($shippingMethod, $systemId) ?: $basePrice;
        }

        // porting to array_reduce would increase complexity of the code because inner function will need a lot of
        // parameters
        foreach ($shippingMethod->getShippingServices() as $methodService) {
            if (($serviceId !== 0 && $methodService->serviceId === $serviceId)
                || ($methodService->departureCountry === $fromCountry
                    && $methodService->destinationCountry === $toCountry)
            ) {
                $baseCost = self::getCostForShippingService(
                    $shippingMethod,
                    $basePrice ?: $methodService->basePrice,
                    $systemId,
                    $totalWeight,
                    $totalPrice
                );

                $cost = min($cost, $baseCost);
            }
        }

        return $cost !== PHP_INT_MAX ? $cost : false;
    }

    /**
     * Calculates shipping cost for given shipping method based on its pricing policy.
     *
     * @param ShippingMethod $method
     * @param float $baseCost Base cost from Packlink API or from default cost.
     * @param string $systemId Unique, ubiquitous system identifier that can be used to identify a system that the pricing policy belongs to.
     * @param float $totalWeight
     * @param float $totalPrice Total amount (weight or value).
     *
     * @return float Calculated shipping cost.
     */
    protected static function getCostForShippingService(
        ShippingMethod $method,
        $baseCost,
        $systemId,
        $totalWeight = 0.0,
        $totalPrice = 0.0
    ) {
        $pricingPolicies = $method->getPricingPolicies();
        if (empty($pricingPolicies)) {
            return $baseCost;
        }

        $cost = PHP_INT_MAX;
        foreach ($pricingPolicies as $policy) {
            if (self::canPolicyBeApplied($policy, $totalWeight, $totalPrice, $systemId)) {
                $cost = self::calculateCost($policy, $baseCost);

                break;
            }
        }

        // if no policy can be applied because of range, use base cost if it is set that way
        if ($cost === PHP_INT_MAX && $method->isUsePacklinkPriceIfNotInRange()) {
            $cost = $baseCost;
        }

        return $cost !== PHP_INT_MAX ? $cost : false;
    }

    /**
     * Returns whether misconfiguration is detected.
     *
     * @param ShippingMethod $method
     *
     * @return bool
     */
    protected static function isMisconfigurationDetected($method)
    {
        if (static::$systemInfo === null) {
            Logger::logError("Currency configuration not found!");

            return false;
        }

        return !in_array($method->getCurrency(), static::$systemInfo->currencies, true);
    }

    /**
     * Returns fallback fixed price when misconfiguration is detected.
     *
     * @param ShippingMethod $method
     * @param string $systemId
     *
     * @return float|null
     */
    protected static function getMisconfigurationFixedPrice($method, $systemId)
    {
        $fixedPrices = $method->getFixedPrices();
        $systemDefaults = $method->getSystemDefaults();

        if (empty($fixedPrices)) {
            return null;
        }

        if (count($fixedPrices) === 1) {
            return (float)reset($fixedPrices);
        }

        if (array_key_exists('default', $fixedPrices)
            && array_key_exists($systemId, $systemDefaults)
            && $systemDefaults[$systemId]
        ) {
            return (float)$fixedPrices['default'];
        }

        if (array_key_exists($systemId, $fixedPrices)) {
            return (float)$fixedPrices[$systemId];
        }

        return null;
    }

    /**
     * Calculates cost based on default value and percent or Packlink pricing policy.
     *
     * @param ShippingPricePolicy $policy Pricing policy
     * @param float $defaultCost Base cost on which to apply pricing policy.
     *
     * @return float Final cost.
     */
    protected static function calculateCost(ShippingPricePolicy $policy, $defaultCost)
    {
        if ($policy->pricingPolicy === ShippingPricePolicy::POLICY_PACKLINK) {
            return $defaultCost;
        }

        if ($policy->pricingPolicy === ShippingPricePolicy::POLICY_FIXED_PRICE) {
            return $policy->fixedPrice;
        }

        $amount = $defaultCost * ($policy->changePercent / 100);
        if ($policy->increase) {
            return round($defaultCost + $amount, 2);
        }

        return round($defaultCost - $amount, 2);
    }

    /**
     * Prepares packages for transmission.
     *
     * @param Package[] $packages Packages.
     *
     * @return Package Prepared package.
     */
    private static function preparePackages(array $packages = array())
    {
        /** @var PackageTransformer $transformer */
        $transformer = ServiceRegister::getService(PackageTransformer::CLASS_NAME);

        return $transformer->transform($packages);
    }

    /**
     * Indicates whether the given policy can be applied by range for the given parameters.
     *
     * @param ShippingPricePolicy $policy
     * @param float $totalWeight
     * @param float $totalPrice
     * @param string $systemId
     *
     * @return bool
     */
    private static function canPolicyBeApplied(ShippingPricePolicy $policy, $totalWeight, $totalPrice, $systemId)
    {
        if ($policy->systemId !== $systemId) {
            return false;
        }

        $byPrice = $policy->fromPrice <= $totalPrice && (empty($policy->toPrice) || $totalPrice <= $policy->toPrice);
        $byWeight = $policy->fromWeight <= $totalWeight
            && (empty($policy->toWeight) || $totalWeight <= $policy->toWeight);

        switch ($policy->rangeType) {
            case ShippingPricePolicy::RANGE_PRICE:
                return $byPrice;
            case ShippingPricePolicy::RANGE_WEIGHT:
                return $byWeight;
            default:
                return $byPrice && $byWeight;
        }
    }

    /**
     * Gets the cheapest shipping services.
     *
     * @param ShippingService[] $services Shipping services to check.
     * @param ShippingService|null $result The service to compare to.
     * @param int|string $serviceId The ID of service to take into consideration.
     * @param string $toCountry The destination country for the service.
     *
     * @return \Packlink\BusinessLogic\ShippingMethod\Models\ShippingService
     */
    private static function getCheapestService(array $services, $result, $serviceId = '', $toCountry = '')
    {
        foreach ($services as $service) {
            if ((!$toCountry || $service->destinationCountry === $toCountry)
                && (!$serviceId || $serviceId === $service->serviceId)
                && ($result === null || $result->basePrice > $service->basePrice)
            ) {
                $result = $service;
            }
        }

        return $result;
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
