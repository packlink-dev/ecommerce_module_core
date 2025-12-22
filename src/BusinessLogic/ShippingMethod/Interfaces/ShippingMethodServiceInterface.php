<?php

namespace Packlink\BusinessLogic\ShippingMethod\Interfaces;

use Packlink\BusinessLogic\Controllers\DTO\ShippingMethodConfiguration;
use Packlink\BusinessLogic\Http\DTO\Package;
use Packlink\BusinessLogic\Http\DTO\ShippingServiceDetails;
use Packlink\BusinessLogic\Http\DTO\SystemInfo;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;

interface ShippingMethodServiceInterface
{
    /**
     * Returns all shipping methods for current user.
     *
     * @return ShippingMethod[] All shipping methods.
     */
    public function getAllMethods();

    /**
     * Returns all configured shipping methods for current user.
     *
     * @return ShippingMethod[] Active shipping methods.
     */
    public function getActiveMethods();

    /**
     * Returns all shipping methods that are not configured.
     *
     * @return ShippingMethod[] Inactive shipping methods.
     */
    public function getInactiveMethods();

    /**
     * Gets shipping method for provided id.
     *
     * @param int $id Shipping method id.
     *
     * @return ShippingMethod|null Shipping method if found; otherwise, NULL.
     */
    public function getShippingMethod($id);

    /**
     * Creates new shipping method out of data received from Packlink API.
     *
     * @param ShippingServiceDetails $serviceDetails Shipping service details with costs.
     * @param bool $isSpecialService
     *
     * @return ShippingMethod Created shipping method.
     */
    public function add($serviceDetails, $isSpecialService = false);

    /**
     * Creates or Updates shipping method from Packlink data.
     *
     * @param ShippingServiceDetails $serviceDetails
     * @param bool $isSpecialService
     *
     * @return ShippingMethod Created or updated shipping method.
     */
    public function update(ShippingServiceDetails $serviceDetails, $isSpecialService = false);

    /**
     * Saves shipping method.
     *
     * @param ShippingMethod $shippingMethod Shipping method to delete.
     */
    public function save(ShippingMethod $shippingMethod);

    /**
     * Deletes shipping method.
     *
     * @param ShippingMethod $shippingMethod Shipping method to delete.
     *
     * @return bool TRUE if deletion succeeded; otherwise, FALSE.
     */
    public function delete(ShippingMethod $shippingMethod);

    /**
     * Activates shipping method.
     *
     * @param int $id Stored method id.
     *
     * @return bool TRUE if activation succeeded; otherwise, FALSE.
     */
    public function activate($id);

    /**
     * Deactivates shipping method.
     *
     * @param int $id Stored method id.
     *
     * @return bool TRUE if deactivation succeeded; otherwise, FALSE.
     */
    public function deactivate($id);

    /**
     * Checks if any method is activated in shop.
     *
     * @return bool TRUE if any method is activated in shop; otherwise, FALSE.
     */
    public function isAnyMethodActive();

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
    );

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
     * @return array Key-value pairs representing shipping method identifiers and their corresponding shipping costs.
     */
    public function getShippingCosts(
        $fromCountry,
        $fromZip,
        $toCountry,
        $toZip,
        array $packages,
        $totalAmount,
        $systemId = null
    );

    /**
     * Gets shipping method for provided service.
     *
     * @param ShippingServiceDetails $service Packlink service.
     *
     * @return ShippingMethod|null Shipping method if found; otherwise, NULL.
     */
    public function getShippingMethodForService($service, $isSpecialService = false);

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
    );

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
    );
}