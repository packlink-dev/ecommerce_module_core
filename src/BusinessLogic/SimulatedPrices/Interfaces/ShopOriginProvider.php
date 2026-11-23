<?php

namespace Packlink\BusinessLogic\SimulatedPrices\Interfaces;

/**
 * Interface ShopOriginProvider. Implemented by a module to tell where a merchant without a Packlink account
 * ships from (the shop address). Optional: without a registered implementation, or when it returns nothing,
 * the sample parcel ships from the capital city of the selected market.
 *
 * @package Packlink\BusinessLogic\SimulatedPrices\Interfaces
 */
interface ShopOriginProvider
{
    /**
     * Fully qualified name of this interface.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * Returns the shop address used as the origin of the sample parcel.
     *
     * @return array|null Keys: country (ISO 3166-1 alpha-2), postalCode; null when the shop has no usable address.
     */
    public function getOrigin();
}
