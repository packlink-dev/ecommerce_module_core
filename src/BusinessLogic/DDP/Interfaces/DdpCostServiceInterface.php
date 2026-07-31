<?php

namespace Packlink\BusinessLogic\DDP\Interfaces;

use Packlink\BusinessLogic\Order\Objects\Order;

/**
 * Interface DdpCostServiceInterface. Checkout-time retrieval of DDP cost components.
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
     * Retrieves DDP cost components for the given checkout order and ONE service.
     *
     * DDP cost is a function of the goods and the route, not the carrier service, so a single call
     * answers for every DDP-capable service on that route -- pass any eligible service id and apply
     * the result to all of them. The underlying endpoint must never be batched: with more than one
     * entry it mis-attributes results (see ShipmentProductsRequest).
     *
     * Failures must never break checkout: every error path yields null, including the ordinary case
     * of a service that simply has no DDP on this route.
     *
     * @param Order $order Checkout order built by the platform.
     * @param string|int $serviceId Packlink service id to fetch DDP costs for.
     *
     * @return \Packlink\BusinessLogic\DDP\Models\DdpCostResponse|null
     */
    public function getDdpCosts(Order $order, $serviceId);
}
