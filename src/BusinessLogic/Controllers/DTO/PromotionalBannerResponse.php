<?php

namespace Packlink\BusinessLogic\Controllers\DTO;

use Packlink\BusinessLogic\DTO\FrontDto;

/**
 * Class PromotionalBannerResponse. Frontend response carrying the data needed
 * to render the upgrade promotional banner on shipping services / orders / support pages.
 *
 * @package Packlink\BusinessLogic\Controllers\DTO
 */
class PromotionalBannerResponse extends FrontDto
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Unique class key.
     */
    const CLASS_KEY = 'promotional_banner_response';
    /**
     * Normalized plan tier: 'FREE', 'PLUS', 'PREMIUM', or null on API failure.
     *
     * @var string|null
     */
    public $planTier;
    /**
     * Localized banner label text with carrier names and prices, or null.
     *
     * @var string|null
     */
    public $bannerLabel;
    /**
     * Upgrade URL for the merchant's country, or null.
     *
     * @var string|null
     */
    public $upgradeUrl;
    /**
     * Fields for this DTO. Needed for validation and transformation from/to array.
     *
     * @var array
     */
    protected static $fields = array(
        'planTier',
        'bannerLabel',
        'upgradeUrl',
    );
}
