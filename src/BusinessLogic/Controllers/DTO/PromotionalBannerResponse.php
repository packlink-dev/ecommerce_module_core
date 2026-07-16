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
     * Lowercase two-letter UI language code. Selects the CDN display-locale folder
     * for the promotional banner translations (live file, with a baked-in fallback).
     *
     * @var string|null
     */
    public $language;
    /**
     * Lowercase Packlink account platform country, used as the "packlink_pro_<market>"
     * CDN filename suffix. Null when the account country is unknown.
     *
     * @var string|null
     */
    public $platform;
    /**
     * Fully built CDN URL for the promotional banner translation file, composed from the
     * display-locale folder (derived from {@see $language}) and the "packlink_pro_<market>"
     * filename (derived from {@see $platform}), e.g. language "es" + platform "fr" =>
     * "https://cdn.packlink.com/translations/pro/es-ES/packlink_pro_fr.json".
     * Null when the language or platform country is unknown/unsupported.
     *
     * @var string|null
     */
    public $bannerCdnUrl;
    /**
     * Fields for this DTO. Needed for validation and transformation from/to array.
     *
     * @var array
     */
    protected static $fields = array(
        'planTier',
        'bannerLabel',
        'upgradeUrl',
        'language',
        'platform',
        'bannerCdnUrl',
    );
}
