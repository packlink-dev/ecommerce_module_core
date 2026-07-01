if (!window.Packlink) {
    window.Packlink = {};
}

(function () {
    // Group + key under which the baked-in banner defaults live in the loaded translations
    // (Packlink.translations), populated from the per-language country JSON files.
    const BANNER_DEFAULTS_GROUP = 'subscriptionBannerDefaults';
    const CDN_BANNER_LABEL_KEY = 'subscriptions.upgrade-notification.more-features';

    /**
     * Handles promotional banner rendering based on the merchant's subscription plan.
     *
     * The banner element (#pl-promotional-banner) is expected to already be present in the
     * current page template, starting hidden via the pl-hidden class. This controller fetches
     * the plan tier, shows the banner only for FREE merchants (PLUS/PREMIUM or an unavailable
     * plan see nothing), and otherwise fills in the label text and wires the upgrade button.
     *
     * @constructor
     */
    function SubscriptionBannerController() {
        const ajaxService = Packlink.ajaxService;

        let planTier = null;
        let upgradeUrl = null;

        /**
         * Fetches plan data and renders the banner if applicable.
         *
         * @param {{
         *     getSubscriptionPlanUrl: string,
         *     getPromotionalBannerUrl?: string,
         *     bannerTextOverride?: string,
         *     upgradeUrl?: string
         * }} config
         */
        this.init = (config) => {
            if (!config || !config.getSubscriptionPlanUrl) {
                return;
            }

            ajaxService.get(config.getSubscriptionPlanUrl, (response) => {
                planTier = response ? response.planTier : null;

                // The promotional banner is only shown to FREE merchants. PLUS and PREMIUM
                // merchants (or when the plan is unavailable) do not see it.
                if (!planTier || planTier !== 'FREE') {
                    return;
                }

                if (config.getPromotionalBannerUrl) {
                    ajaxService.get(config.getPromotionalBannerUrl, (bannerResponse) => {
                        if (!bannerResponse) {
                            return;
                        }

                        upgradeUrl = bannerResponse.upgradeUrl;
                        resolveBannerText(bannerResponse, renderBanner);
                    });
                } else if (config.bannerTextOverride) {
                    upgradeUrl = config.upgradeUrl;
                    renderBanner(config.bannerTextOverride);
                }
            });
        };

        /**
         * Returns the fetched plan tier (available after the init callback resolves).
         *
         * @return {string|null}
         */
        this.getPlanTier = () => planTier;

        /**
         * Resolves the banner text. The CDN file URL is built on the server
         * (bannerResponse.bannerCdnUrl) from the UI language and account platform country.
         * Prefers the live CDN copy, falls back to the baked-in default for the merchant's
         * market (from the loaded translations, see getDefaultBannerText), and finally to the
         * server-built bannerResponse.bannerLabel (unsupported language/platform, fetch
         * unavailable, request failure, or a missing banner key).
         *
         * @param {{bannerCdnUrl?: string, platform?: string, bannerLabel?: string}} bannerResponse
         * @param {function(string)} callback
         */
        const resolveBannerText = (bannerResponse, callback) => {
            const fallback = bannerResponse.bannerLabel;
            const url = bannerResponse.bannerCdnUrl || null;
            const platform = bannerResponse.platform ? bannerResponse.platform.toLowerCase() : null;

            const bakedIn = getDefaultBannerText(platform) || fallback;

            if (!url || typeof window.fetch !== 'function') {
                callback(bakedIn);
                return;
            }

            window.fetch(url)
                .then((response) => (response.ok ? response.json() : Promise.reject()))
                .then((data) => {
                    const text = data && data[CDN_BANNER_LABEL_KEY];
                    callback(text || bakedIn);
                })
                .catch(() => callback(bakedIn));
        };

        /**
         * Looks up the baked-in default banner text for the merchant's market from the loaded
         * translations (Packlink.translations). The current language is already selected by
         * which translations are loaded; the platform (market) picks the carrier-specific copy.
         * Falls back from the current language to the default language.
         *
         * @param {string|null} platform Lowercase platform country code.
         *
         * @return {string|null}
         */
        const getDefaultBannerText = (platform) => {
            if (!platform || !window.Packlink.translations) {
                return null;
            }

            const marketKey = 'packlink_pro_' + platform;
            const current = Packlink.translations.current || {};
            const fallback = Packlink.translations.default || {};
            const market = (current[BANNER_DEFAULTS_GROUP] || {})[marketKey]
                || (fallback[BANNER_DEFAULTS_GROUP] || {})[marketKey]
                || null;

            return market ? market[CDN_BANNER_LABEL_KEY] || null : null;
        };

        /**
         * Populates the banner DOM and reveals it.
         *
         * @param {string} text
         */
        const renderBanner = (text) => {
            const banner = document.getElementById('pl-promotional-banner');

            if (!banner || !text) {
                return;
            }

            const textEl = document.getElementById('pl-promotional-banner-text');
            const buttonEl = document.getElementById('pl-promotional-banner-button');

            if (textEl) {
                textEl.textContent = text;
            }

            if (buttonEl) {
                buttonEl.addEventListener('click', () => {
                    if (upgradeUrl) {
                        window.open(upgradeUrl, '_blank');
                    }
                });
            }

            banner.classList.remove('pl-hidden');
        };
    }

    Packlink.SubscriptionBannerController = SubscriptionBannerController;
})();
