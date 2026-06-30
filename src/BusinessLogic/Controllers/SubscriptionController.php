<?php

namespace Packlink\BusinessLogic\Controllers;

use Logeecom\Infrastructure\Configuration\Configuration as InfrastructureConfiguration;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Controllers\DTO\PromotionalBannerResponse;
use Packlink\BusinessLogic\Controllers\DTO\SubscriptionPlanResponse;
use Packlink\BusinessLogic\Subscription\SubscriptionService;

/**
 * Class SubscriptionController. Aggregates subscription plan + promotional banner
 * data for the frontend.
 *
 * @package Packlink\BusinessLogic\Controllers
 */
class SubscriptionController
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * Country-to-Packlink-domain mapping for the upgrade URL.
     */
    private static $platformDomains = array(
        'ES' => 'es',
        'FR' => 'fr',
        'DE' => 'de',
        'IT' => 'it',
    );

    /**
     * UI language => CDN display-locale folder. Selects the locale subfolder of the
     * promotional banner translation file on the CDN.
     */
    private static $displayLocales = array(
        'es' => 'es-ES',
        'en' => 'en-GB',
        'fr' => 'fr-FR',
        'it' => 'it-IT',
        'de' => 'de-DE',
    );

    /**
     * Base URL of the CDN folder holding the promotional banner translation files.
     */
    const CDN_BASE_URL = 'https://cdn.packlink.com/translations/pro';

    /**
     * Generic English label sent as the banner text. The frontend prefers the live
     * CDN copy (and a baked-in per-locale fallback); this is the final fallback used
     * when neither is availavbble.
     */
    const GENERIC_BANNER_LABEL = 'Unlock exclusive shipping benefits with access to the best carrier rates, '
        . 'premium refrigerated delivery services, and the freedom to ship using your own negotiated carrier rates.';

    /**
     * @var SubscriptionService
     */
    private $subscriptionService;
    /**
     * @var Configuration
     */
    private $configuration;

    public function __construct()
    {
        $this->subscriptionService = ServiceRegister::getService(SubscriptionService::CLASS_NAME);
        $this->configuration = ServiceRegister::getService(Configuration::CLASS_NAME);
    }

    /**
     * Returns the merchant's subscription plan tier and display name.
     * Both fields are null when the API call fails (frontend should hide the plan label).
     *
     * @return SubscriptionPlanResponse
     */
    public function getPlan()
    {
        $subscription = $this->subscriptionService->getActiveSubscription();

        $response = new SubscriptionPlanResponse();

        if ($subscription === null) {
            $response->planTier = null;
            $response->planName = null;

            return $response;
        }

        $response->planTier = $subscription->getPlanTier();
        $response->planName = $subscription->plan !== null ? $subscription->plan->name : null;

        return $response;
    }

    /**
     * Returns promotional banner data for the shipping services page.
     *
     * For PREMIUM merchants (or when the API call fails) the response carries
     * a null tier so the frontend hides the banner.
     *
     * @return PromotionalBannerResponse
     */
    public function getPromotionalBanner()
    {
        $response = new PromotionalBannerResponse();

        $subscription = $this->subscriptionService->getActiveSubscription();

        if ($subscription === null) {
            $response->planTier = null;

            return $response;
        }

        $response->planTier = $subscription->getPlanTier();

        if ($response->planTier === 'PREMIUM') {
            return $response;
        }

        $country = $this->getMerchantCountry();

        $response->upgradeUrl = $this->buildUpgradeUrl($country);
        $response->bannerLabel = self::GENERIC_BANNER_LABEL;
        $response->language = $this->getSystemLanguage();
        $response->platform = $country !== '' ? strtolower($country) : null;
        $response->bannerCdnUrl = $this->buildBannerCdnUrl($response->language, $response->platform);

        return $response;
    }

    /**
     * Builds the CDN URL of the promotional banner translation file from the UI language
     * (mapped to a display-locale folder) and the platform country (used as the
     * "packlink_pro_<market>" filename suffix).
     *
     * @param string|null $language Lowercase two-letter UI language code.
     * @param string|null $platform Lowercase platform country code.
     *
     * @return string|null Full CDN URL, or null when the language or platform is unknown/unsupported.
     */
    private function buildBannerCdnUrl($language, $platform)
    {
        if (empty($language) || empty($platform) || !isset(self::$displayLocales[$language])) {
            return null;
        }

        $locale = self::$displayLocales[$language];

        return self::CDN_BASE_URL . '/' . $locale . '/packlink_pro_' . $platform . '.json';
    }

    /**
     * @return string Two-letter uppercase country code, or empty string if unknown.
     */
    private function getMerchantCountry()
    {
        $userInfo = $this->configuration->getUserInfo();

        return $userInfo !== null ? strtoupper((string)$userInfo->country) : '';
    }

    /**
     * @param string $country
     *
     * @return string
     */
    private function buildUpgradeUrl($country)
    {
        $domain = isset(self::$platformDomains[$country]) ? self::$platformDomains[$country] : 'com';

        return 'https://pro.packlink.' . $domain . '/private/subscriptions';
    }

    /**
     * @return string Lowercase two-letter language code from the UI configuration.
     */
    private function getSystemLanguage()
    {
        $language = InfrastructureConfiguration::getUICountryCode();

        return strtolower((string)$language);
    }
}
