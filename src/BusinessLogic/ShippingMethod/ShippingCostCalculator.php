<?php /** @noinspection MoreThanThreeArgumentsInspection */

namespace Packlink\BusinessLogic\ShippingMethod;

use Logeecom\Infrastructure\Http\Exceptions\HttpBaseException;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Http\DTO\Package;
use Packlink\BusinessLogic\Http\DTO\ShippingServiceDetails;
use Packlink\BusinessLogic\Http\DTO\ShippingServiceSearch;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\ShippingMethod\Exceptions\FixedPriceValueOutOfBoundsException;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingService;

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
     * @param Package[] $packages Array of packages if calculation is done by weight.
     * @param float $totalAmount Total cart value if calculation is done by value
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
        $totalAmount
    ) {
        $data = self::getShippingCosts(
            array($shippingMethod),
            $fromCountry,
            $fromZip,
            $toCountry,
            $toZip,
            $packages,
            $totalAmount
        );
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
     * @param Package[] $packages Array of packages if calculation is done by weight.
     * @param float $totalAmount Total cart value if calculation is done by value
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
        $totalAmount
    ) {
        if (empty($shippingMethods)) {
            return array();
        }

        $result = array();
        $package = self::preparePackages($packages);
        $params = new ShippingServiceSearch(null, $fromCountry, $fromZip, $toCountry, $toZip, array($package));
        try {
            $response = self::getProxy()->getShippingServicesDeliveryDetails($params);

            $result = self::calculateShippingCostsPerShippingMethod(
                $shippingMethods,
                $response,
                $package->weight,
                $totalAmount
            );
        } catch (HttpBaseException $e) {
            // Fallback when API is not available.
            if ($e->getCode() !== 400) {
                $result = self::getDefaultShippingCosts(
                    $shippingMethods,
                    $fromCountry,
                    $toCountry,
                    $package->weight,
                    $totalAmount
                );
            }
        }

        return $result;
    }

    /**
     * Returns cheapest service in shipping method.
     *
     * @param \Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod $method
     * @param string $fromCountry From country code.
     * @param string $fromZip From zip code.
     * @param string $toCountry To country code.
     * @param string $toZip To zip code.
     * @param Package[] $packages Packages for which to find service.
     *
     * @return ShippingService Cheapest service.
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
        $searchParams = new ShippingServiceSearch(null, $fromCountry, $fromZip, $toCountry, $toZip, array($package));

        /** @var Proxy $proxy */
        $proxy = ServiceRegister::getService(Proxy::CLASS_NAME);
        try {
            $services = $proxy->getShippingServicesDeliveryDetails($searchParams);
        } catch (\Exception $e) {
            $services = array();
        }

        /** @var ShippingService $result */
        $result = null;
        if (!empty($services)) {
            foreach ($services as $service) {
                foreach ($method->getShippingServices() as $methodService) {
                    if ($service->id === $methodService->serviceId
                        && ($result === null || $result->basePrice > $methodService->basePrice)
                    ) {
                        $result = $methodService;
                    }
                }
            }
        } else {
            // Fallback.
            foreach ($method->getShippingServices() as $service) {
                if ($service->destinationCountry === $toCountry) {
                    if ($result === null || $result->basePrice > $service->basePrice) {
                        $result = $service;
                    }
                }
            }
        }

        if ($result !== null) {
            return $result;
        }

        throw new \InvalidArgumentException(
            'No service is available for shipping method '
            . $method->getId() . ' for given destination country ' . $toCountry
            . ' and given packages ' . json_encode($packages)
        );
    }

    /**
     * Calculates shipping method cost based on given criteria.
     *
     * @param \Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod $shippingMethod
     * @param float $totalAmount
     *
     * @param int $serviceId
     * @param float $basePrice
     *
     * @param string $fromCountry
     * @param string $toCountry
     *
     * @return float|bool Calculated cost or FALSE if cost cannot be calculated for the given criteria.
     */
    protected static function calculateShippingMethodCost(
        ShippingMethod $shippingMethod,
        $totalAmount,
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
                try {
                    $baseCost = self::calculateCostForShippingMethod(
                        $shippingMethod,
                        $basePrice ?: $methodService->basePrice,
                        $totalAmount
                    );
                } catch (FixedPriceValueOutOfBoundsException $e) {
                    return false;
                }

                $cost = min($cost, $baseCost);
            }
        }

        return $cost !== PHP_INT_MAX ? $cost : false;
    }

    /**
     * Calculates shipping cost for given shipping method based on its pricing policy.
     *
     * @param ShippingMethod $shippingMethod Method to calculate cost for.
     * @param float $baseCost Base cost from Packlink API or from default cost.
     * @param float $totalAmount Total amount (weight or value).
     *
     * @return float Calculated shipping cost.
     *
     * @throws \Packlink\BusinessLogic\ShippingMethod\Exceptions\FixedPriceValueOutOfBoundsException
     */
    protected static function calculateCostForShippingMethod(
        ShippingMethod $shippingMethod,
        $baseCost,
        $totalAmount = 0.0
    ) {
        $pricingPolicy = $shippingMethod->getPricingPolicy();
        if ($pricingPolicy === ShippingMethod::PRICING_POLICY_FIXED_PRICE_BY_WEIGHT
            || $pricingPolicy === ShippingMethod::PRICING_POLICY_FIXED_PRICE_BY_VALUE
        ) {
            return round(self::calculateFixedPriceCost($shippingMethod, $totalAmount), 2);
        }

        return round(self::calculateVariableCost($shippingMethod, $baseCost), 2);
    }

    /**
     * Calculates shipping cost for fixed price policy.
     *
     * @param ShippingMethod $shippingMethod Method to calculate cost for.
     * @param float $total Total weight or value.
     *
     * @return float Calculated fixed price cost.
     *
     * @throws \Packlink\BusinessLogic\ShippingMethod\Exceptions\FixedPriceValueOutOfBoundsException
     */
    protected static function calculateFixedPriceCost(ShippingMethod $shippingMethod, $total)
    {
        $fixedPricePolicies = $shippingMethod->getFixedPriceByWeightPolicy();
        if ($shippingMethod->getPricingPolicy() === ShippingMethod::PRICING_POLICY_FIXED_PRICE_BY_VALUE) {
            $fixedPricePolicies = $shippingMethod->getFixedPriceByValuePolicy();
        }

        self::sortFixedPricePolicy($fixedPricePolicies);

        if ($total < $fixedPricePolicies[0]->from) {
            throw new FixedPriceValueOutOfBoundsException('Fixed price value out of bounds.');
        }

        foreach ($fixedPricePolicies as $fixedPricePolicy) {
            if ($fixedPricePolicy->from <= $total && $fixedPricePolicy->to > $total) {
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
     * @param float $totalWeight Package total weight.
     * @param float $totalAmount Total cart value if calculation is done by value
     *
     * @return array Array of shipping cost per service. Key is service id and value is shipping cost.
     */
    protected static function getDefaultShippingCosts(
        array $shippingMethods,
        $fromCountry,
        $toCountry,
        $totalWeight,
        $totalAmount
    ) {
        $shippingCosts = array();

        /** @var ShippingMethod $shippingMethod */
        foreach ($shippingMethods as $shippingMethod) {
            $cost = self::calculateShippingMethodCost(
                $shippingMethod,
                self::getAmountBasedOnPricingPolicy($shippingMethod, $totalAmount, $totalWeight),
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
     * @param float $totalAmount Total value if calculation is done by value
     *
     * @return array Array of shipping cost per service. Key is service id and value is shipping cost.
     */
    private static function calculateShippingCostsPerShippingMethod(
        array $shippingMethods,
        array $shippingServices,
        $totalWeight,
        $totalAmount
    ) {
        $shippingCosts = array();

        /** @var ShippingMethod $method */
        foreach ($shippingMethods as $method) {
            $amount = self::getAmountBasedOnPricingPolicy($method, $totalAmount, $totalWeight);

            $cost = PHP_INT_MAX;
            foreach ($shippingServices as $service) {
                $baseCost = self::calculateShippingMethodCost($method, $amount, $service->id, $service->basePrice);
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

    /**
     * @param \Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod $method
     * @param $totalAmount
     * @param $totalWeight
     *
     * @return int
     *
     */
    private static function getAmountBasedOnPricingPolicy(ShippingMethod $method, $totalAmount, $totalWeight)
    {
        $pricingPolicy = $method->getPricingPolicy();
        $amount = 0;
        if ($pricingPolicy === ShippingMethod::PRICING_POLICY_FIXED_PRICE_BY_WEIGHT) {
            $amount = $totalWeight;
        } elseif ($pricingPolicy === ShippingMethod::PRICING_POLICY_FIXED_PRICE_BY_VALUE) {
            $amount = $totalAmount;
        }

        return $amount;
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
     * Sorts fixed price policies.
     *
     * @param \Packlink\BusinessLogic\ShippingMethod\Models\FixedPricePolicy[] $fixedPricePolicies
     *      Reference to an fixed price policy array.
     */
    private static function sortFixedPricePolicy(&$fixedPricePolicies)
    {
        usort(
            $fixedPricePolicies,
            function ($a, $b) {
                if ($a->from === $b->from) {
                    return 0;
                }

                return $a->from < $b->from ? -1 : 1;
            }
        );
    }
}
