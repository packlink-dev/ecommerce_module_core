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
     * Retrieves DDP cost components for the given checkout order and services.
     * Failures must never break checkout: every error path yields an empty array.
     *
     * @param Order $order Checkout order built by the platform.
     * @param string[]|int[] $serviceIds Packlink service ids to fetch DDP costs for.
     *
     * @return \Packlink\BusinessLogic\DDP\Models\DdpCostResponse[] DDP costs keyed by service id.
     */
    public function getDdpCosts(Order $order, array $serviceIds);
}
