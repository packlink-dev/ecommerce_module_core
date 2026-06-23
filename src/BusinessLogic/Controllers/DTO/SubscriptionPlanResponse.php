<?php

namespace Packlink\BusinessLogic\Controllers\DTO;

use Packlink\BusinessLogic\DTO\FrontDto;

/**
 * Class SubscriptionPlanResponse. Frontend response carrying the merchant's
 * subscription plan tier and display name.
 *
 * @package Packlink\BusinessLogic\Controllers\DTO
 */
class SubscriptionPlanResponse extends FrontDto
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Unique class key.
     */
    const CLASS_KEY = 'subscription_plan_response';
    /**
     * Normalized plan tier: 'FREE', 'PLUS', 'PREMIUM', or null on API failure.
     *
     * @var string|null
     */
    public $planTier;
    /**
     * Human-readable plan name from the API (e.g. "Free", "Plus", "Premium"), or null.
     *
     * @var string|null
     */
    public $planName;
    /**
     * Fields for this DTO. Needed for validation and transformation from/to array.
     *
     * @var array
     */
    protected static $fields = array(
        'planTier',
        'planName',
    );
}
