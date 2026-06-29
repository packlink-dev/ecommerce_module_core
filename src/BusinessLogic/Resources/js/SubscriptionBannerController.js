if (!window.Packlink) {
    window.Packlink = {};
}

(function () {
    /**
     * Handles promotional banner rendering based on the merchant's subscription plan.
     *
     * The banner element (#pl-promotional-banner) is expected to already be present in the
     * current page template, starting hidden via the pl-hidden class. This controller fetches
     * the plan tier, hides the banner for PREMIUM merchants (or when the plan is unavailable),
     * and otherwise fills in the label text and wires the upgrade button.
     *
     * @constructor
     */
    function SubscriptionBannerController() {
        const ajaxService = Packlink.ajaxService;

        // Flat key in the CDN translation file holding the promotional banner text.
        const CDN_BANNER_LABEL_KEY = 'subscriptions.upgrade-notification.more-features';

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

                if (!planTier || planTier === 'PREMIUM') {
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
         * Resolves the banner text, preferring the localized copy from the Packlink CDN
         * (bannerResponse.bannerCdnUrl) and falling back to the server-built
         * bannerResponse.bannerLabel on any failure: no URL, unsupported browser,
         * network error, non-OK response, or a missing banner key.
         *
         * @param {{bannerCdnUrl?: string, bannerLabel?: string}} bannerResponse
         * @param {function(string)} callback
         */
        const resolveBannerText = (bannerResponse, callback) => {
            const fallback = bannerResponse.bannerLabel;
            const cdnUrl = bannerResponse.bannerCdnUrl;

            if (!cdnUrl || typeof window.fetch !== 'function') {
                callback(fallback);
                return;
            }

            window.fetch(cdnUrl)
                .then((response) => (response.ok ? response.json() : Promise.reject()))
                .then((data) => {
                    const text = data && data[CDN_BANNER_LABEL_KEY];
                    callback(text || fallback);
                })
                .catch(() => callback(fallback));
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
