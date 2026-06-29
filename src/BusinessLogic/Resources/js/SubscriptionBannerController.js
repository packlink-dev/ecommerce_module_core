if (!window.Packlink) {
    window.Packlink = {};
}

(function () {
    // Flat key in the CDN translation file holding the promotional banner text.
    const CDN_BANNER_LABEL_KEY = 'subscriptions.upgrade-notification.more-features';

    /**
     * Baked-in default subscription banner translations, keyed by their CDN URL. Used
     * as the offline/unreachable fallback for the live CDN fetch. One entry per
     * supported UI language; the merchant market is baked into each file.
     */
    const SUBSCRIPTION_BANNER_DEFAULTS = {
        "https://cdn.packlink.com/translations/pro/es-ES/packlink_pro_es.json": {
            "subscriptions.free-plan-features.carrier-contracts": "Integración de contratos con transportistas (Correos, Seur y Correos Express)",
            "subscriptions.free-plan-prices.shipping-rate": "Descuentos en todas las tarifas de envío con Correos, Correos Express, Seur, UPS, InPost y más.",
            "subscriptions.plus-plan-features.carrier-contracts": "Conecta 1 contrato con transportista (Correos, Seur y Correos Express)",
            "subscriptions.plus-plan-prices.shipping-rate": "Descuentos Plus, tarifas de envío desde 2,47€ con Correos y desde 4,44€ con Correos Express.",
            "subscriptions.premium-plan-features.carrier-contracts": "Contratos con transportistas ilimitados (Correos, Seur y Correos Express)",
            "subscriptions.premium-plan-prices.shipping-rate": "Descuentos Premium, tarifas de envío desde 2,38€ con Correos y desde 4,35€ con Correos Express.",
            "subscriptions.upgrade-notification.more-features": "Actualiza a Plus y empieza a enviar desde 2,47 € con Correos y desde 4,44 € con Correos Express para paquetes de hasta 1 kg 💎!"
        },
        "https://cdn.packlink.com/translations/pro/en-GB/packlink_pro_es.json": {
            "subscriptions.free-plan-features.carrier-contracts": "Carrier contract integration (Correos, Seur and Correos Express)",
            "subscriptions.free-plan-prices.shipping-rate": "Discounts on all shipping rates with Correos, Correos Express, Seur, UPS, InPost and more.",
            "subscriptions.plus-plan-features.carrier-contracts": "Connect 1 carrier contract (Correos, Seur and Correos Express)",
            "subscriptions.plus-plan-prices.shipping-rate": "Plus discounts, shipping rates starting from €2.47 with Correos and from €4.44 with Correos Express.",
            "subscriptions.premium-plan-features.carrier-contracts": "Unlimited carrier contracts (Correos, Seur and Correos Express)",
            "subscriptions.premium-plan-prices.shipping-rate": "Premium discounts, shipping rates starting from €2.38 with Correos and from €4.35 with Correos Express.",
            "subscriptions.upgrade-notification.more-features": "Upgrade to Plus and start shipping from €2.47 with Correos and from €4.44 with Correos Express for packages up to 1 kg 💎!"
        },
        "https://cdn.packlink.com/translations/pro/fr-FR/packlink_pro_fr.json": {
            "subscriptions.free-plan-features.carrier-contracts": "Intégration de contrats transporteur (Chronopost)",
            "subscriptions.free-plan-prices.shipping-rate": "Réductions sur tous les tarifs d'expédition avec Chronopost, Mondial Relay, Colissimo et plus encore.",
            "subscriptions.plus-plan-features.carrier-contracts": "Connectez 1 contrat transporteur (Chronopost)",
            "subscriptions.plus-plan-prices.shipping-rate": "Remises Plus, tarifs d'expédition à partir de 2,84€ avec Chronopost et à partir de 2,89€ avec Mondial Relay.",
            "subscriptions.premium-plan-features.carrier-contracts": "Contrats transporteur illimités (Chronopost)",
            "subscriptions.premium-plan-prices.shipping-rate": "Remises Premium, tarifs d'expédition à partir de 2,84€ avec Chronopost et à partir de 2,89€ avec Mondial Relay.",
            "subscriptions.upgrade-notification.more-features": "Passez à Plus et commencez à expédier dès 2,84 € avec Chronopost et dès 2,89 € avec Mondial Relay pour des colis jusqu'à 500 g 💎!"
        },
        "https://cdn.packlink.com/translations/pro/it-IT/packlink_pro_it.json": {
            "subscriptions.feature-list.exclusive-prices": "Sconti Plus, tariffe di spedizione a partire da 3,82€ per Poste Italiane e 4,50€ per BRT.",
            "subscriptions.free-plan-features.carrier-contracts": "Integrazione contratti corriere (BRT e Poste Italiane)",
            "subscriptions.free-plan-prices.shipping-rate": "Sconti su tutte le tariffe di spedizione con Poste Italiane, BRT, InPost e altri.",
            "subscriptions.plus-plan-features.carrier-contracts": "Collega 1 contratto corriere (BRT e Poste Italiane)",
            "subscriptions.plus-plan-prices.shipping-rate": "Sconti Plus, tariffe di spedizione a partire da 3,82€ per Poste Italiane e 4,50€ per BRT.",
            "subscriptions.premium-plan-features.carrier-contracts": "Contratti corriere illimitati (BRT e Poste Italiane)",
            "subscriptions.premium-plan-prices.shipping-rate": "Sconti Premium, tariffe di spedizione a partire da 3,70€ per Poste Italiane e 4,35€ per BRT.",
            "subscriptions.upgrade-notification.more-features": "Passa a Plus e inizia a spedire da 3,82 € con Poste Italiane e da 4,50 € con BRT per pacchi fino a 2 kg 💎!"
        },
        "https://cdn.packlink.com/translations/pro/de-DE/packlink_pro_de.json": {
            "subscriptions.free-plan-prices.shipping-rate": "Rabatte auf alle Versandtarife mit DPD, UPS, DHL Express, TNT und mehr.",
            "subscriptions.plus-plan-prices.shipping-rate": "Plus-Rabatte, Versandkosten ab 3,97€ mit DPD XS und ab 4,65€ mit UPS.",
            "subscriptions.premium-plan-prices.shipping-rate": "Premium-Rabatte, Versandkosten ab 3,97€ mit DPD XS und ab 4,65€ mit UPS.",
            "subscriptions.upgrade-notification.more-features": "Upgrade auf Plus und versende ab 3,97 € mit DPD XS und ab 4,65 € mit UPS für Pakete bis 500 g 💎!"
        }
    };

    const CDN_BASE_URL = 'https://cdn.packlink.com/translations/pro';

    /**
     * UI language => display-locale folder, derived from the bundled defaults' own
     * keys (".../pro/es-ES/..." => es:"es-ES", en:"en-GB"). No separate locale map is
     * maintained; the data is the single source of truth for the folder names.
     */
    const localeByLanguage = {};
    Object.keys(SUBSCRIPTION_BANNER_DEFAULTS).forEach((url) => {
        const match = url.match(/\/pro\/(([a-z]{2})-[A-Z]{2})\//);
        if (match) {
            localeByLanguage[match[2]] = match[1];
        }
    });

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
         * Resolves the banner text. The CDN file is selected by composing the
         * display-locale folder (from the UI language) with the platform market suffix
         * (from the account platform country), e.g. language "es" + platform "fr" =>
         * .../pro/es-ES/packlink_pro_fr.json. Prefers the live CDN copy, falls back to
         * the baked-in default for that URL, and finally to the server-built
         * bannerResponse.bannerLabel (unsupported language/platform, fetch unavailable,
         * request failure, or a missing banner key).
         *
         * @param {{language?: string, platform?: string, bannerLabel?: string}} bannerResponse
         * @param {function(string)} callback
         */
        const resolveBannerText = (bannerResponse, callback) => {
            const fallback = bannerResponse.bannerLabel;
            const language = bannerResponse.language ? bannerResponse.language.toLowerCase() : null;
            const platform = bannerResponse.platform ? bannerResponse.platform.toLowerCase() : null;
            const locale = language ? localeByLanguage[language] : null;

            if (!locale || !platform) {
                callback(fallback);
                return;
            }

            const url = CDN_BASE_URL + '/' + locale + '/packlink_pro_' + platform + '.json';
            const defaults = SUBSCRIPTION_BANNER_DEFAULTS[url];
            const bakedIn = (defaults && defaults[CDN_BANNER_LABEL_KEY]) || fallback;

            if (typeof window.fetch !== 'function') {
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
