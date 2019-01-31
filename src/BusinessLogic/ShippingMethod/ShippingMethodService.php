<?php

namespace Packlink\BusinessLogic\ShippingMethod;

use Logeecom\Infrastructure\Http\Exceptions\HttpBaseException;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface;
use Logeecom\Infrastructure\ORM\QueryFilter\Operators;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\BaseService;
use Packlink\BusinessLogic\Http\DTO\Package;
use Packlink\BusinessLogic\Http\DTO\ShippingService;
use Packlink\BusinessLogic\Http\DTO\ShippingServiceDeliveryDetails;
use Packlink\BusinessLogic\Http\DTO\ShippingServiceSearch;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethodCost;

/**
 * Class ShippingMethodService. In charge for manipulation with shipping methods and services.
 *
 * @package Packlink\BusinessLogic\ShippingMethod
 */
class ShippingMethodService extends BaseService
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
     * Shop shipping method service.
     *
     * @var ShopShippingMethodService
     */
    private $shopShippingMethodService;
    /**
     * Shipping method repository.
     *
     * @var RepositoryInterface
     */
    private $shippingMethodRepository;

    /**
     * ShippingMethodService constructor.
     */
    protected function __construct()
    {
        parent::__construct();

        $this->shopShippingMethodService = ServiceRegister::getService(ShopShippingMethodService::CLASS_NAME);
        $this->shippingMethodRepository = $this->getRepository(ShippingMethod::CLASS_NAME);
    }

    /**
     * Returns all shipping methods for current user.
     *
     * @return ShippingMethod[] All shipping methods.
     */
    public function getAllMethods()
    {
        return $this->select();
    }

    /**
     * Returns all shipping methods for current user.
     *
     * @return ShippingMethod[] All shipping methods.
     */
    public function getActiveMethods()
    {
        $filter = $this->setFilterCondition(new QueryFilter(), 'activated', Operators::EQUALS, true);

        return $this->select($filter);
    }

    /**
     * Gets shipping method for provided id.
     *
     * @param int $id Shipping method id.
     *
     * @return ShippingMethod|null Shipping method if found; otherwise, NULL.
     */
    public function getShippingMethod($id)
    {
        $filter = $this->setFilterCondition(new QueryFilter(), 'id', Operators::EQUALS, $id);

        return $this->selectOne($filter);
    }

    /**
     * Creates new shipping method out of data received from Packlink API.
     * This method is alias for method @see update.
     *
     * @param ShippingService $service Shipping service data.
     * @param ShippingServiceDeliveryDetails $serviceDetails Shipping service details with costs.
     *
     * @return ShippingMethod Created shipping method.
     */
    public function add(ShippingService $service, ShippingServiceDeliveryDetails $serviceDetails)
    {
        return $this->update($service, $serviceDetails);
    }

    /**
     * Creates or Updates shipping method from Packlink data.
     *
     * @param ShippingService $service Shipping service data update.
     * @param ShippingServiceDeliveryDetails $serviceDetails
     *
     * @return ShippingMethod Created or updated shipping method.
     */
    public function update(ShippingService $service, ShippingServiceDeliveryDetails $serviceDetails)
    {
        $method = $this->getShippingMethodForService($service->id);
        if ($method === null) {
            $method = new ShippingMethod();
        }

        $this->setShippingMethodDetails($method, $service, $serviceDetails);

        $this->save($method);

        return $method;
    }

    /**
     * Saves shipping method.
     *
     * @param ShippingMethod $shippingMethod Shipping method to delete.
     */
    public function save(ShippingMethod $shippingMethod)
    {
        if ($shippingMethod->getId()) {
            $this->shippingMethodRepository->update($shippingMethod);
        } else {
            $this->shippingMethodRepository->save($shippingMethod);
        }

        if ($shippingMethod->isActivated()) {
            $this->shopShippingMethodService->update($shippingMethod);
        }
    }

    /**
     * Deletes shipping method.
     *
     * @param ShippingMethod $shippingMethod Shipping method to delete.
     *
     * @return bool TRUE if deletion succeeded; otherwise, FALSE.
     */
    public function delete(ShippingMethod $shippingMethod)
    {
        $result = !$shippingMethod->isActivated();

        if ($shippingMethod->isActivated()) {
            $result = $this->shopShippingMethodService->delete($shippingMethod);
        }

        if ($result) {
            $result = $this->shippingMethodRepository->delete($shippingMethod);
        }

        return $result;
    }

    /**
     * Activates shipping method for provided Packlink service.
     *
     * @param int $serviceId Packlink service identifier.
     *
     * @return bool TRUE if activation succeeded; otherwise, FALSE.
     */
    public function activate($serviceId)
    {
        return $this->setActivationState($serviceId, true);
    }

    /**
     * Deactivates shipping method for provided Packlink service.
     *
     * @param int $serviceId Packlink service identifier.
     *
     * @return bool TRUE if deactivation succeeded; otherwise, FALSE.
     */
    public function deactivate($serviceId)
    {
        return $this->setActivationState($serviceId, false);
    }

    /**
     * Checks if any method is activated in shop.
     *
     * @return bool TRUE if any method is activated in shop; otherwise, FALSE.
     */
    public function isAnyMethodActive()
    {
        $filter = $this->setFilterCondition(new QueryFilter(), 'activated', Operators::EQUALS, true);

        return $this->shippingMethodRepository->count($filter) > 0;
    }

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
    public function getShippingCost($serviceId, $fromCountry, $fromZip, $toCountry, $toZip, array $packages)
    {
        /** @var Proxy $proxy */
        $proxy = ServiceRegister::getService(Proxy::CLASS_NAME);

        $defaultCost = 0;
        $shippingMethod = $this->getShippingMethodForService($serviceId);
        if ($shippingMethod === null) {
            return $defaultCost;
        }

        if ($shippingMethod->getPricingPolicy() === ShippingMethod::PRICING_POLICY_FIXED) {
            return round($this->calculateFixedPriceCost($shippingMethod, $fromCountry, $toCountry, $packages), 2);
        }

        try {
            $response = $proxy->getShippingServicesDeliveryDetails(
                $this->getCostSearchParameters($fromCountry, $fromZip, $toCountry, $toZip, $packages, $serviceId)
            );
            if (count($response)) {
                $defaultCost = $response[0]->basePrice;
            }
        } catch (HttpBaseException $e) {
            // fallback when API is not available.
            $defaultCost = $this->getDefaultCost($shippingMethod, $fromCountry, $toCountry);
        }

        return $defaultCost ? round($this->calculateVariableCost($shippingMethod, $defaultCost), 2) : 0;
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
    public function getShippingCosts($fromCountry, $fromZip, $toCountry, $toZip, array $packages)
    {
        $activeMethods = $this->getActiveMethods();
        if (empty($activeMethods)) {
            return array();
        }

        try {
            $shippingCosts = $this->getShippingCostsFromProxy(
                $fromCountry,
                $fromZip,
                $toCountry,
                $toZip,
                $packages,
                $activeMethods
            );
        } catch (HttpBaseException $e) {
            // Fallback when API is not available.
            $shippingCosts = $this->getDefaultShippingCosts($fromCountry, $toCountry, $activeMethods);
        }

        $calculatedShippingCosts = $this->calculateShippingCosts(
            $fromCountry,
            $toCountry,
            $packages,
            $shippingCosts
        );

        return $calculatedShippingCosts;
    }

    /**
     * Gets shipping method for provided service.
     *
     * @param int $serviceId Packlink service identifier.
     *
     * @return ShippingMethod|null Shipping method if found; otherwise, NULL.
     */
    public function getShippingMethodForService($serviceId)
    {
        $filter = $this->setFilterCondition(new QueryFilter(), 'serviceId', Operators::EQUALS, $serviceId);

        return $this->selectOne($filter);
    }

    /**
     * Activates or deactivates shipping method for provided Packlink service.
     *
     * @param int $serviceId Packlink service id.
     * @param bool $activated TRUE if service is being activated.
     *
     * @return bool TRUE if setting state succeeded; otherwise, FALSE.
     */
    protected function setActivationState($serviceId, $activated)
    {
        $method = $this->getShippingMethodForService($serviceId);
        if ($method === null) {
            Logger::logWarning('Shipping method for service ' . $serviceId . ' does not exist.');

            return false;
        }

        if ($this->setActivationStateInShop($activated, $method)) {
            $method->setActivated($activated);

            return $this->shippingMethodRepository->update($method);
        }

        Logger::logWarning('Could not activate/deactivate shipping service ' . $serviceId . ' in shop.');

        return false;
    }

    /**
     * Activates or deactivates shipping method in shop.
     *
     * @param bool $activated TRUE if service is being activated.
     * @param ShippingMethod $method Shipping method.
     *
     * @return bool TRUE if setting state succeeded; otherwise, FALSE.
     */
    protected function setActivationStateInShop($activated, ShippingMethod $method)
    {
        if ($activated) {
            return $this->shopShippingMethodService->add($method);
        }

        return $this->shopShippingMethodService->delete($method);
    }

    /**
     * Sets information to shipping method from Packlink API details.
     *
     * @param ShippingMethod $shippingMethod Shipping method to update.
     * @param ShippingService $service Packlink shipping service.
     * @param ShippingServiceDeliveryDetails $serviceDetails Details for shipping service.
     */
    protected function setShippingMethodDetails(
        ShippingMethod $shippingMethod,
        ShippingService $service,
        ShippingServiceDeliveryDetails $serviceDetails
    ) {
        $shippingMethod->setServiceId($service->id);
        $shippingMethod->setServiceName($service->serviceName);
        $shippingMethod->setCarrierName($service->carrierName);
        $shippingMethod->setLogoUrl($service->logoUrl);
        $shippingMethod->setDepartureDropOff($service->departureDropOff);
        $shippingMethod->setDestinationDropOff($service->destinationDropOff);
        $shippingMethod->setEnabled($service->enabled);

        $shippingMethod->setDeliveryTime($serviceDetails->transitTime);
        $shippingMethod->setExpressDelivery($serviceDetails->expressDelivery);
        $shippingMethod->setNational($serviceDetails->departureCountry === $serviceDetails->destinationCountry);

        $this->setShippingCosts($shippingMethod, $serviceDetails);
    }

    /**
     * Sets shipping costs on selected method.
     *
     * @param ShippingMethod $shippingMethod Method to be updated.
     * @param ShippingServiceDeliveryDetails $serviceDetails Shipping details to get costs from.
     */
    protected function setShippingCosts(ShippingMethod $shippingMethod, ShippingServiceDeliveryDetails $serviceDetails)
    {
        $cost = new ShippingMethodCost(
            $serviceDetails->departureCountry,
            $serviceDetails->destinationCountry,
            $serviceDetails->totalPrice,
            $serviceDetails->basePrice,
            $serviceDetails->taxPrice
        );

        $set = false;
        foreach ($shippingMethod->getShippingCosts() as $currentCost) {
            if ($currentCost->departureCountry === $cost->departureCountry
                && $currentCost->destinationCountry === $cost->destinationCountry
            ) {
                // update
                $currentCost->basePrice = $cost->basePrice;
                $currentCost->totalPrice = $cost->totalPrice;
                $currentCost->taxPrice = $cost->taxPrice;

                $set = true;

                break;
            }
        }

        if (!$set) {
            // merge
            $costs = $shippingMethod->getShippingCosts();
            $costs[] = $cost;
            $shippingMethod->setShippingCosts($costs);
        }
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
     * @param ShippingMethod[] $activeMethods Array of active shipping methods in the system.
     *
     * @return array Array of prepared data for shipping costs calculation.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    protected function getShippingCostsFromProxy(
        $fromCountry,
        $fromZip,
        $toCountry,
        $toZip,
        array $packages,
        array $activeMethods
    ) {
        /** @var Proxy $proxy */
        $proxy = ServiceRegister::getService(Proxy::CLASS_NAME);

        $response = $proxy->getShippingServicesDeliveryDetails(
            $this->getCostSearchParameters($fromCountry, $fromZip, $toCountry, $toZip, $packages)
        );

        $shippingCosts = $this->prepareDataForShippingCostsCalculation($response, $activeMethods);

        return $shippingCosts;
    }

    /**
     * Prepares raw response data for shipping costs calculation.
     *
     * @param ShippingServiceDeliveryDetails[] $shippingServiceDeliveries Array of shipping service delivery details.
     * @param ShippingMethod[] $activeMethods Array of active shipping methods in the system.
     *
     * @return array Array of prepared data for shipping cost calculation.
     */
    protected function prepareDataForShippingCostsCalculation(
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

            $shippingMethod = $this->getShippingMethodForService($shippingServiceDeliveryDetails->id);
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
     * Calculates shipping costs based on their pricing policy.
     *
     * @param string $fromCountry Departure country code.
     * @param string $toCountry Destination country code.
     * @param Package[] $packages Array of parcels.
     * @param array $shippingCosts Array of prepared data that needs to be calculated.
     *
     * @return array Calculated shipping costs.
     */
    protected function calculateShippingCosts(
        $fromCountry,
        $toCountry,
        array $packages,
        array $shippingCosts
    ) {
        $calculatedShippingCosts = array();

        foreach ($shippingCosts as $serviceId => $shippingCost) {
            $shippingMethod = $this->getShippingMethodForService($serviceId);
            if ($shippingMethod === null) {
                continue;
            }

            if ($shippingMethod->getPricingPolicy() === ShippingMethod::PRICING_POLICY_FIXED) {
                $calculatedShippingCosts[$shippingMethod->getServiceId()] = round(
                    $this->calculateFixedPriceCost($shippingMethod, $fromCountry, $toCountry, $packages),
                    2
                );
            } else {
                $calculatedShippingCosts[$shippingMethod->getServiceId()] = round(
                    $this->calculateVariableCost($shippingMethod, $shippingCost),
                    2
                );
            }
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
    protected function getDefaultShippingCosts($fromCountry, $toCountry, array $activeMethods)
    {
        $shippingCosts = array();

        foreach ($activeMethods as $shippingMethod) {
            if ($shippingMethod->getPricingPolicy() === ShippingMethod::PRICING_POLICY_FIXED) {
                $shippingCosts[$shippingMethod->getServiceId()] = 0;
            } else {
                $shippingCosts[$shippingMethod->getServiceId()] = $this->getDefaultCost(
                    $shippingMethod,
                    $fromCountry,
                    $toCountry
                );
            }
        }

        return $shippingCosts;
    }

    /**
     * Gets default shipping cost from shipping method.
     *
     * @param ShippingMethod $shippingMethod A method to get costs from.
     * @param string $fromCountry Departure country code.
     * @param string $toCountry Destination country code.
     *
     * @return float Default cost from shipping method.
     */
    private function getDefaultCost($shippingMethod, $fromCountry, $toCountry)
    {
        foreach ($shippingMethod->getShippingCosts() as $shippingCost) {
            if ($shippingCost->departureCountry === $fromCountry
                && $shippingCost->destinationCountry === $toCountry
            ) {
                return $shippingCost->basePrice;
            }
        }

        return 0;
    }

    /**
     * Calculates shipping cost for fixed price policy.
     *
     * @param ShippingMethod $shippingMethod Shipping method.
     * @param string $fromCountry Departure country code.
     * @param string $toCountry Destination country code.
     * @param Package[] $packages Array of packages.
     *
     * @return float Calculated fixed price cost.
     */
    protected function calculateFixedPriceCost(
        ShippingMethod $shippingMethod,
        $fromCountry,
        $toCountry,
        array $packages
    ) {
        if ($this->getDefaultCost($shippingMethod, $fromCountry, $toCountry) === 0) {
            // this method is not available for selected departure and destination
            return 0;
        }

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
     * @param ShippingMethod $shippingMethod
     * @param float $defaultCost Base cost on which to apply pricing policy.
     *
     * @return float Final cost.
     */
    protected function calculateVariableCost($shippingMethod, $defaultCost)
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
     * Transforms parameters to proper DTO.
     *
     * @param string $fromCountry Departure country code.
     * @param string $fromZip Departure zip code.
     * @param string $toCountry Destination country code.
     * @param string $toZip Destination zip code.
     * @param Package[] $packages Parcel info.
     * @param int $serviceId ID of service to calculate costs for.
     *
     * @return \Packlink\BusinessLogic\Http\DTO\ShippingServiceSearch Resulting object
     */
    protected function getCostSearchParameters(
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
     * Sets filter condition. Wrapper method for suppressing warning.
     *
     * @param QueryFilter $filter Filter object.
     * @param string $column Column name.
     * @param string $operator Operator. Use constants from @see Operator class.
     * @param mixed $value Value of condition.
     *
     * @return QueryFilter Filter for chaining.
     */
    protected function setFilterCondition(QueryFilter $filter, $column, $operator, $value = null)
    {
        try {
            return $filter->where($column, $operator, $value);
        } catch (QueryFilterInvalidParamException $e) {
        }

        return $filter;
    }

    /**
     * Executes select query.
     *
     * @param QueryFilter $filter Filter for query.
     *
     * @return ShippingMethod[] A list of found shipping methods.
     */
    protected function select($filter = null)
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->shippingMethodRepository->select($filter);
    }

    /**
     * Executes select query.
     *
     * @param QueryFilter $filter Filter for query.
     *
     * @return ShippingMethod First found shipping method.
     */
    protected function selectOne($filter)
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->shippingMethodRepository->selectOne($filter);
    }
}
