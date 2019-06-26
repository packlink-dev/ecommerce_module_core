<?php

namespace Packlink\BusinessLogic\ShippingMethod\Interfaces;

use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;

/**
 * Interface ShopShippingMethodService. Must be implemented in integration.
 *
 * @package Packlink\BusinessLogic\ShippingMethod
 */
interface ShopShippingMethodService
{
    /**
     * Fully qualified name of this interface.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * Adds / Activates shipping method in shop integration.
     *
     * @param ShippingMethod $shippingMethod Shipping method.
     *
     * @return bool TRUE if activation succeeded; otherwise, FALSE.
     */
    public function add(ShippingMethod $shippingMethod);

    /**
     * Updates shipping method in shop integration.
     *
     * @param ShippingMethod $shippingMethod Shipping method.
     */
    public function update(ShippingMethod $shippingMethod);

    /**
     * Deletes shipping method in shop integration.
     *
     * @param ShippingMethod $shippingMethod Shipping method.
     *
     * @return bool TRUE if deletion succeeded; otherwise, FALSE.
     */
    public function delete(ShippingMethod $shippingMethod);
}
