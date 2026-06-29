<?php

namespace Packlink\BusinessLogic\Controllers;

use Exception;
use Logeecom\Infrastructure\Configuration\Configuration as InfrastructureConfiguration;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Controllers\DTO\PromotionalBannerResponse;
use Packlink\BusinessLogic\Controllers\DTO\SubscriptionPlanResponse;
use Packlink\BusinessLogic\Http\DTO\Package;
use Packlink\BusinessLogic\Http\DTO\ShippingServiceSearch;
use Packlink\BusinessLogic\Http\Proxy;
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
     * Two highlighted carriers per supported country (CR-65a spec).
     */
    private static $highlightedCarriers = array(
        'ES' => array('Correos', 'SEUR'),
        'FR' => array('Colissimo', 'Mondial Relay'),
        'IT' => array('Poste Italiane', 'BRT'),
        'DE' => array('DPD', 'GLS'),
    );

    /**
     * Representative postal codes used when querying /v1/services for the banner.
     * ShippingServiceSearch::isValid() requires from/to zip codes, so a fixed
     * national capital zip per country is used.
     */
    private static $representativePostalCodes = array(
        'ES' => '28001',
        'FR' => '75001',
        'IT' => '00118',
        'DE' => '10115',
    );

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
     * Language-keyed banner templates. Placeholders in order:
     * %1$s = target plan name (Plus / Premium),
     * %2$s = first price (formatted), %3$s = first carrier,
     * %4$s = second price (formatted), %5$s = second carrier.
     */
    private static $bannerTemplates = array(
        'en' => 'Upgrade to %1$s and start shipping from %2$s EUR with %3$s and from %4$s EUR with %5$s for parcels up to 1 kg.',
        'es' => 'Pasate a %1$s y empieza a enviar desde %2$s EUR con %3$s y desde %4$s EUR con %5$s para paquetes de hasta 1 kg.',
        'fr' => 'Passez a %1$s et commencez a expedier des %2$s EUR avec %3$s et des %4$s EUR avec %5$s pour des colis jusqu\'a 1 kg.',
        'it' => 'Passa a %1$s e inizia a spedire a partire da %2$s EUR con %3$s e da %4$s EUR con %5$s per pacchi fino a 1 kg!',
        'de' => 'Wechseln Sie zu %1$s und versenden Sie schon ab %2$s EUR mit %3$s und ab %4$s EUR mit %5$s fur Pakete bis zu 1 kg.',
    );

    /**
     * Generic English label shown to merchants whose country is not in the
     * highlighted-carrier map (spec AC-4.2.8).
     */
    const GENERIC_BANNER_LABEL = 'Unlock exclusive shipping benefits with access to the best carrier rates, '
        . 'premium refrigerated delivery services, and the freedom to ship using your own negotiated carrier rates.';

    /**
     * Base URL of the Packlink CDN translation files that carry the localized
     * promotional banner copy. The frontend fetches the language-specific file and
     * reads {@see self::CDN_BANNER_LABEL_KEY} from it.
     */
    const CDN_TRANSLATIONS_BASE_URL = 'https://cdn.packlink.com/translations/pro';

    /**
     * Flat key in the CDN translation file holding the promotional banner text.
     */
    const CDN_BANNER_LABEL_KEY = 'subscriptions.upgrade-notification.more-features';

    /**
     * Module UI language to CDN display-locale folder. The folder selects the
     * language the banner copy is rendered in. Only languages with a published CDN
     * folder are listed; any other UI language yields a null CDN URL.
     */
    private static $cdnDisplayLocales = array(
        'es' => 'es-ES',
        'fr' => 'fr-FR',
        'de' => 'de-DE',
        'it' => 'it-IT',
        'en' => 'en-GB',
    );

    /**
     * Packlink account platform-country to CDN market language code, used as the
     * "packlink_pro_<market>" filename suffix. The suffix selects which market's
     * subscription banner content is loaded. Only countries with a published CDN
     * file are listed; any other account country yields a null CDN URL.
     */
    private static $cdnMarketLanguages = array(
        'ES' => 'es',
        'FR' => 'fr',
        'DE' => 'de',
        'IT' => 'it',
    );

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
        $response->bannerLabel = $this->buildBannerLabel($response->planTier, $country);
        $response->bannerCdnUrl = $this->buildBannerCdnUrl($this->getSystemLanguage(), $country);

        return $response;
    }

    /**
     * Builds the CDN URL for the promotional banner content, or null when no CDN file
     * applies. The display-locale folder follows the module UI language and the market
     * language suffix follows the merchant's Packlink account platform country, e.g.
     * UI "es" + account "FR" => .../pro/es-ES/packlink_pro_fr.json. Returns null when
     * either the UI language or the account country is unsupported, leaving the banner
     * server-driven.
     *
     * @param string $language Lowercase two-letter module UI language code.
     * @param string $country Two-letter uppercase Packlink account platform country.
     *
     * @return string|null
     */
    private function buildBannerCdnUrl($language, $country)
    {
        if (!isset(self::$cdnDisplayLocales[$language]) || !isset(self::$cdnMarketLanguages[$country])) {
            return null;
        }

        return self::CDN_TRANSLATIONS_BASE_URL . '/' . self::$cdnDisplayLocales[$language]
            . '/packlink_pro_' . self::$cdnMarketLanguages[$country] . '.json';
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
     * Highlighted carriers come from the merchant's platform country (ES/FR/IT/DE);
     * the template language follows the system UI language. When the merchant's
     * platform has no highlighted carriers, OR the system language has no localized
     * template, the generic English label is returned.
     *
     * @param string $planTier 'FREE' or 'PLUS'.
     * @param string $country Two-letter uppercase platform country code.
     *
     * @return string
     */
    private function buildBannerLabel($planTier, $country)
    {
        if (!isset(self::$highlightedCarriers[$country])) {
            return self::GENERIC_BANNER_LABEL;
        }

        $language = $this->getSystemLanguage();

        if (!isset(self::$bannerTemplates[$language])) {
            return self::GENERIC_BANNER_LABEL;
        }

        $carriers = self::$highlightedCarriers[$country];
        $prices = $this->fetchCarrierPrices($country, $carriers);
        $upgradeTo = $planTier === 'FREE' ? 'Plus' : 'Premium';

        return sprintf(
            self::$bannerTemplates[$language],
            $upgradeTo,
            number_format($prices[0], 2, ',', ''),
            $carriers[0],
            number_format($prices[1], 2, ',', ''),
            $carriers[1]
        );
    }

    /**
     * @return string Lowercase two-letter language code from the UI configuration.
     */
    private function getSystemLanguage()
    {
        $language = InfrastructureConfiguration::getUICountryCode();

        return strtolower((string)$language);
    }

    /**
     * Fetches the lowest available 1kg price from /v1/services for each highlighted carrier.
     * Returns 0.0 for any carrier with no matching service (or on any error).
     *
     * @param string $country
     * @param string[] $carriers Two carrier names.
     *
     * @return float[] Two prices in the same order as $carriers.
     */
    private function fetchCarrierPrices($country, array $carriers)
    {
        $prices = array(0.0, 0.0);

        if (!isset(self::$representativePostalCodes[$country])) {
            return $prices;
        }

        try {
            /** @var Proxy $proxy */
            $proxy = ServiceRegister::getService(Proxy::CLASS_NAME);

            $zip = self::$representativePostalCodes[$country];
            $search = new ShippingServiceSearch(
                null,
                $country,
                $zip,
                $country,
                $zip,
                array(new Package(1.0, 10.0, 10.0, 10.0))
            );

            $services = $proxy->getShippingServicesDeliveryDetails($search);

            foreach ($services as $service) {
                foreach ($carriers as $index => $carrierName) {
                    if (stripos((string)$service->carrierName, $carrierName) === false) {
                        continue;
                    }

                    if ($prices[$index] === 0.0 || $service->totalPrice < $prices[$index]) {
                        $prices[$index] = (float)$service->totalPrice;
                    }
                }
            }
        } catch (Exception $e) {
            Logger::logError(
                'Failed to fetch carrier prices for promotional banner: ' . $e->getMessage(),
                'Core'
            );
        }

        return $prices;
    }
}
