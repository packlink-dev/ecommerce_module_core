<?php

namespace Packlink\BusinessLogic\DDP;

/**
 * Class DdpBehavior. Holds constants for merchant DDP behavior configuration,
 * the effective behavior resolution, and the Packlink API DDP support levels.
 *
 * @package Packlink\BusinessLogic\DDP
 */
class DdpBehavior
{
    /**
     * No duties charged on checkout.
     */
    const NONE = 'none';
    /**
     * Customer can choose between DDP and regular delivery.
     */
    const OPTIONAL = 'optional';
    /**
     * Merchant enforces DDP on a service that supports it.
     */
    const ENFORCED = 'enforced';
    /**
     * DDP is mandatory on the service (API-driven, merchant configuration is ignored).
     */
    const MANDATORY = 'mandatory';
    /**
     * DDP cost adjustment type: fixed amount.
     */
    const ADJUSTMENT_FIXED = 'fixed';
    /**
     * DDP cost adjustment type: percentage of the DDP cost.
     */
    const ADJUSTMENT_PERCENTAGE = 'percentage';
    /**
     * API support level: service supports DDP.
     */
    const LEVEL_SUPPORTED = 'supported';
    /**
     * API support level: service requires DDP.
     */
    const LEVEL_MANDATORY = 'mandatory';
}
