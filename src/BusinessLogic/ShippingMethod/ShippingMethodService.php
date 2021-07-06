<?php

namespace Packlink\BusinessLogic\ShippingMethod;

use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface;
use Logeecom\Infrastructure\ORM\QueryFilter\Operators;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\BaseService;
use Packlink\BusinessLogic\Controllers\DTO\ShippingMethodConfiguration;
use Packlink\BusinessLogic\Http\DTO\Package;
use Packlink\BusinessLogic\Http\DTO\ShippingServiceDetails;
use Packlink\BusinessLogic\Http\DTO\SystemInfo;
use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingPricePolicy;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingService;

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
     * Returns all configured shipping methods for current user.
     *
     * @return ShippingMethod[] Active shipping methods.
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function getActiveMethods()
    {
        $filter = $this->setFilterCondition(new QueryFilter(), 'activated', Operators::EQUALS, true);
        /** @noinspection PhpUnhandledExceptionInspection */
        $filter->orderBy('carrierName');

        return $this->select($filter);
    }

    /**
     * Returns all shipping methods that are not configured.
     *
     * @return ShippingMethod[] Inactive shipping methods.
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function getInactiveMethods()
    {
        $filter = $this->setFilterCondition(new QueryFilter(), 'activated', Operators::EQUALS, false);
        /** @noinspection PhpUnhandledExceptionInspection */
        $filter->orderBy('carrierName');

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
     * This method is alias for method @param ShippingServiceDetails $serviceDetails Shipping service details with costs.
     *
     * @return ShippingMethod Created shipping method.
     * @see update.
     *
     */
    public function add(ShippingServiceDetails $serviceDetails)
    {
        return $this->update($serviceDetails);
    }

    /**
     * Creates or Updates shipping method from Packlink data.
     *
     * @param ShippingServiceDetails $serviceDetails
     *
     * @return ShippingMethod Created or updated shipping method.
     */
    public function update(ShippingServiceDetails $serviceDetails)
    {
        $method = $this->getShippingMethodForService($serviceDetails);
        if ($method === null) {
            $method = new ShippingMethod();
        }

        $this->setShippingMethodDetails($method, $serviceDetails);

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
            if ($shippingMethod->isActivated()) {
                $this->shopShippingMethodService->update($shippingMethod);
            }
        } else {
            $this->shippingMethodRepository->save($shippingMethod);
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
            $result = $this->deleteShopShippingMethod($shippingMethod);
        }

        if ($result) {
            $result = $this->shippingMethodRepository->delete($shippingMethod);
        }

        return $result;
    }

    /**
     * Activates shipping method.
     *
     * @param int $id Shipping method entity identifier.
     *
     * @return bool TRUE if activation succeeded; otherwise, FALSE.
     */
    public function activate($id)
    {
        return $this->setActivationState($id, true);
    }

    /**
     * Deactivates shipping method.
     *
     * @param int $id Shipping method entity identifier.
     *
     * @return bool TRUE if deactivation succeeded; otherwise, FALSE.
     */
    public function deactivate($id)
    {
        return $this->setActivationState($id, false);
    }

    /**
     * Checks if any method is activated in shop.
     *
     * @return bool TRUE if any method is activated in shop; otherwise, FALSE.
     */
    public function isAnyMethodActive()
    {
        return $this->getNumberOfActiveShippingMethods() > 0;
    }

    /**
     * Returns shipping costs for given shipping service for delivery of specified packages from specified
     * departure country and postal area to specified destination country and postal area.
     *
     * @param int $methodId Id of shipping method entity for which to calculate costs.
     * @param string $fromCountry Departure country code.
     * @param string $fromZip Departure zip code.
     * @param string $toCountry Destination country code.
     * @param string $toZip Destination zip code.
     * @param Package[] $packages Array of packages if calculation is done by weight.
     * @param float $totalAmount Total cart value if calculation is done by value
     * @param string|null $systemId Unique, ubiquitous system identifier that can be used to identify a system that the pricing policy belongs to.
     *
     * @return float Calculated shipping cost
     */
    public function getShippingCost(
        $methodId,
        $fromCountry,
        $fromZip,
        $toCountry,
        $toZip,
        array $packages,
        $totalAmount,
        $systemId = null
    ) {
        $shippingMethod = $this->getShippingMethod($methodId);
        if ($shippingMethod === null || !$shippingMethod->isActivated()) {
            Logger::logWarning(
                'Tried to calculate shipping cost for method that does not exist in shop '
                . 'or is not activated (' . $methodId . ')'
            );

            return 0;
        }

        return ShippingCostCalculator::getShippingCost(
            $shippingMethod,
            $fromCountry,
            $fromZip,
            $toCountry,
            $toZip,
            $packages,
            $totalAmount,
            $systemId
        );
    }

    /**
     * Returns shipping costs for all available shipping services that support delivery of given packages
     * from specified departure country and postal area to specified destination country and postal area.
     *
     * @param string $fromCountry Departure country code.
     * @param string $fromZip Departure zip code.
     * @param string $toCountry Destination country code.
     * @param string $toZip Destination zip code.
     * @param Package[] $packages Array of packages if calculation is done by weight.
     * @param float $totalAmount Total cart value if calculation is done by value
     * @param string|null $systemId Unique, ubiquitous system identifier that can be used to identify a system that the pricing policy belongs to.
     *
     * @return array <p>Key-value pairs representing shipping method identifiers and their corresponding shipping costs.
     *  array(
     *     20345 => 34.47,
     *     20337 => 27.11,
     *     ...
     *  )
     * </p>
     */
    public function getShippingCosts(
        $fromCountry,
        $fromZip,
        $toCountry,
        $toZip,
        array $packages,
        $totalAmount,
        $systemId = null
    ) {
        $activeMethods = $this->getActiveMethods();
        if (empty($activeMethods)) {
            return array();
        }

        return ShippingCostCalculator::getShippingCosts(
            $activeMethods,
            $fromCountry,
            $fromZip,
            $toCountry,
            $toZip,
            $packages,
            $totalAmount,
            $systemId
        );
    }

    /**
     * Gets shipping method for provided service.
     *
     * @param ShippingServiceDetails $service Packlink service.
     *
     * @return ShippingMethod|null Shipping method if found; otherwise, NULL.
     */
    public function getShippingMethodForService($service)
    {
        $filter = new QueryFilter();

        try {
            $filter->where('departureDropOff', Operators::EQUALS, $service->departureDropOff)
                ->where('destinationDropOff', Operators::EQUALS, $service->destinationDropOff)
                ->where('national', Operators::EQUALS, $service->national)
                ->where('expressDelivery', Operators::EQUALS, $service->expressDelivery)
                ->where('carrierName', Operators::EQUALS, $service->carrierName);
        } catch (QueryFilterInvalidParamException $e) {
            return null;
        }

        $methods = $this->select($filter);
        foreach ($methods as $method) {
            if ($method->getCurrency() === $service->currency) {
                return $method;
            }
        }

        return null;
    }

    /**
     * Check whether currency configurations on pricing policies are supported by the system.
     *
     * @param SystemInfo $systemInfo
     * @param ShippingMethodConfiguration $configuration
     * @param ShippingMethod $method
     *
     * @return bool
     */
    public function isCurrencyConfigurationValidForSingleStore(
        SystemInfo $systemInfo,
        ShippingMethodConfiguration $configuration,
        ShippingMethod $method
    ) {
        if (!$this->isShippingMethodCurrencyConfigurationValidForSingleStore(
            $configuration,
            $systemInfo,
            $method->getCurrency()
        )) {
            return false;
        }

        foreach ($configuration->pricingPolicies as $policy) {
            if(!$this->isCurrencyConfigurationForPricingPolicyValid($policy, $systemInfo, $method->getCurrency())) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns whether the shipping method currency configuration is valid for a single store.
     *
     * @param ShippingMethodConfiguration $configuration
     * @param SystemInfo $detail
     * @param string $currency
     *
     * @return bool
     */
    public function isShippingMethodCurrencyConfigurationValidForSingleStore(
        ShippingMethodConfiguration $configuration,
        SystemInfo $detail,
        $currency
    ) {
        if (in_array($currency, $detail->currencies, true)) {
            return true;
        }

        $defaultPriceExists = !empty($configuration->fixedPrices)
            && array_key_exists('default', $configuration->fixedPrices);
        $usesDefault = !empty($configuration->systemDefaults)
            && array_key_exists($detail->systemId, $configuration->systemDefaults)
            && $configuration->systemDefaults[$detail->systemId];

        if ($usesDefault && !$defaultPriceExists) {
            return false;
        }

        if (!$usesDefault && !$this->fixedPriceExists($configuration, $detail->systemId)) {
            return false;
        }

        return true;
    }

    /**
     * Validates currency configuration for a single pricing policy.
     *
     * @param ShippingPricePolicy $policy
     * @param SystemInfo $systemInfo
     * @param string $currency
     *
     * @return bool
     */
    protected function isCurrencyConfigurationForPricingPolicyValid(
        ShippingPricePolicy $policy,
        SystemInfo $systemInfo,
        $currency
    ) {
        if ($policy->systemId !== $systemInfo->systemId) {
            return true;
        }

        if ($policy->pricingPolicy === ShippingPricePolicy::POLICY_FIXED_PRICE) {
            return true;
        }

        return in_array($currency, $systemInfo->currencies, true);
    }

    /**
     * Returns whether fixed price is configured for this system.
     *
     * @param ShippingMethodConfiguration $configuration
     * @param $systemId
     *
     * @return bool
     */
    protected function fixedPriceExists(ShippingMethodConfiguration $configuration, $systemId)
    {
        return !empty($configuration->fixedPrices) && array_key_exists($systemId, $configuration->fixedPrices);
    }

    /**
     * Activates or deactivates shipping method for provided Packlink service.
     *
     * @param int $id Stored method id.
     * @param bool $activated TRUE if service is being activated.
     *
     * @return bool TRUE if setting state succeeded; otherwise, FALSE.
     */
    protected function setActivationState($id, $activated)
    {
        $method = $this->getShippingMethod($id);
        if ($method === null) {
            Logger::logWarning('Shipping method for ID ' . $id . ' does not exist.');

            return false;
        }

        if ($this->setActivationStateInShop($activated, $method)) {
            $method->setActivated($activated);

            return $this->shippingMethodRepository->update($method);
        }

        Logger::logWarning('Could not activate/deactivate shipping method ' . $id . ' in shop.');

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
            return $this->addShopShippingMethod($method);
        }

        return $this->deleteShopShippingMethod($method);
    }

    /**
     * Adds shop shipping method.
     *
     * @param ShippingMethod $shippingMethod
     *
     * @return bool TRUE if adding shop shipping method succeeds; FALSE otherwise.
     */
    protected function addShopShippingMethod(ShippingMethod $shippingMethod)
    {
        $result = $this->shopShippingMethodService->add($shippingMethod);

        if ($result && !$this->isAnyMethodActive()) {
            // New instance has to be used to avoid propagating any changes made in addBackupShippingMethod
            // to original shipping method instance.
            $newInstance = ShippingMethod::fromArray($shippingMethod->toArray());
            $result = $this->shopShippingMethodService->addBackupShippingMethod($newInstance);
        }

        return $result;
    }

    /**
     * Deletes shop shipping method.
     *
     * @param ShippingMethod $shippingMethod
     *
     * @return bool TRUE if deleting shop shipping method succeeds; FALSE otherwise.
     */
    protected function deleteShopShippingMethod(ShippingMethod $shippingMethod)
    {
        $result = $this->shopShippingMethodService->delete($shippingMethod);

        if ($result && $this->getNumberOfActiveShippingMethods() === 1) {
            $result = $this->shopShippingMethodService->deleteBackupShippingMethod();
        }

        return $result;
    }

    /**
     * Sets information to shipping method from Packlink API details.
     *
     * @param ShippingMethod $shippingMethod Shipping method to update.
     * @param ShippingServiceDetails $serviceDetails Details for shipping service.
     */
    protected function setShippingMethodDetails(
        ShippingMethod $shippingMethod,
        ShippingServiceDetails $serviceDetails
    ) {
        $shippingMethod->setCarrierName($serviceDetails->carrierName);
        $shippingMethod->setDepartureDropOff($serviceDetails->departureDropOff);
        $shippingMethod->setDestinationDropOff($serviceDetails->destinationDropOff);
        $shippingMethod->setDeliveryTime($serviceDetails->transitTime);
        $shippingMethod->setExpressDelivery($serviceDetails->expressDelivery);
        $shippingMethod->setNational($serviceDetails->departureCountry === $serviceDetails->destinationCountry);
        $logoUrl = $this->shopShippingMethodService->getCarrierLogoFilePath($serviceDetails->carrierName);
        $shippingMethod->setLogoUrl($logoUrl);
        $shippingMethod->setEnabled(true);
        $shippingMethod->setCurrency($serviceDetails->currency);

        $this->setShippingService($shippingMethod, $serviceDetails);
    }

    /**
     * Sets shipping service on selected method.
     *
     * @param ShippingMethod $shippingMethod Method to be updated.
     * @param ShippingServiceDetails $service Shipping service.
     */
    protected function setShippingService(ShippingMethod $shippingMethod, ShippingServiceDetails $service)
    {
        $newService = ShippingService::fromServiceDetails($service);
        $set = false;
        foreach ($shippingMethod->getShippingServices() as $currentService) {
            if ($currentService->serviceId === $newService->serviceId
                && $currentService->departureCountry === $newService->departureCountry
                && $currentService->destinationCountry === $newService->destinationCountry
            ) {
                $currentService->serviceName = $newService->serviceName;
                $currentService->basePrice = $newService->basePrice;
                $currentService->totalPrice = $newService->totalPrice;
                $currentService->taxPrice = $newService->taxPrice;
                $set = true;

                break;
            }
        }

        if (!$set) {
            $shippingMethod->addShippingService($newService);
        }
    }

    /**
     * Retrieves number of active shipping methods.
     *
     * @return int Number of active shipping methods.
     */
    protected function getNumberOfActiveShippingMethods()
    {
        $filter = $this->setFilterCondition(new QueryFilter(), 'activated', Operators::EQUALS, true);

        return $this->shippingMethodRepository->count($filter);
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
     * @noinspection PhpIncompatibleReturnTypeInspection
     */
    protected function select($filter = null)
    {
        return $this->shippingMethodRepository->select($filter);
    }

    /**
     * Executes select one query.
     *
     * @param QueryFilter $filter Filter for query.
     *
     * @return ShippingMethod First found shipping method.
     * @noinspection PhpIncompatibleReturnTypeInspection
     */
    protected function selectOne($filter)
    {
        return $this->shippingMethodRepository->selectOne($filter);
    }
}