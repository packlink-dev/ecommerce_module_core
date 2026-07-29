<?php

namespace Packlink\BusinessLogic\DDP\Interfaces;

use Packlink\BusinessLogic\Order\Objects\Order;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;

/**
 * Interface DdpCostServiceInterface. Checkout-time retrieval of DDP cost components
 * and resolution of the effective DDP behavior for a shipping method.
 *
 * @package Packlink\BusinessLogic\DDP\Interfaces
 */
interface DdpCostServiceInterface
{
    /**
     * Fully qualified name of this interface.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * Retrieves DDP cost components for the given checkout order and services.
     * Failures must never break checkout: every error path yields an empty array.
     *
     * @param Order $order Checkout order built by the platform.
     * @param string[]|int[] $serviceIds Packlink service ids to fetch DDP costs for.
     *
     * @return \Packlink\BusinessLogic\DDP\Models\DdpCostResponse[] DDP costs keyed by service id.
     */
    public function getDdpCosts(Order $order, array $serviceIds);

    /**
     * Resolves the effective DDP behavior from the method's API support level
     * and the merchant-configured behavior.
     *
     * @param ShippingMethod $method Shipping method.
     *
     * @return string One of DdpBehavior::NONE, OPTIONAL, ENFORCED, MANDATORY.
     */
    public function resolveEffectiveBehavior(ShippingMethod $method);
}
