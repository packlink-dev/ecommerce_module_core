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
     * API support level: service supports DDP.
     */
    const LEVEL_SUPPORTED = 'supported';
    /**
     * API support level: service requires DDP.
     */
    const LEVEL_MANDATORY = 'mandatory';
}
