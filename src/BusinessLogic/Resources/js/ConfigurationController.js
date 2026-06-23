if (!window.Packlink) {
    window.Packlink = {};
}

(function () {
    /**
     * Handles configuration static page.
     *
     * @constructor
     *
     * @param {{getDataUrl: string}} config
     */
    function ConfigurationController(config) {

        const templateService = Packlink.templateService,
            state = Packlink.state,
            ajaxService = Packlink.ajaxService,
            utilityService = Packlink.utilityService,
            translationService = Packlink.translationService,
            templateId = 'pl-configuration-page';

        /**
         *
         * @param {{helpUrl: string, version: string, email: string}} response
         */
        const setConfigParams = (response) => {
            const version = templateService.getComponent('pl-version-number'),
                helpLink = templateService.getComponent('pl-navigate-help');

            version.innerHTML = 'v' + response.version;
            helpLink.href = response.helpUrl;

            const emailValue = templateService.getComponent('pl-account-email-value');
            if (emailValue && response.email) {
                emailValue.textContent = response.email;
            }

            templateService.getComponent('pl-open-system-info').addEventListener('click', () => {
                state.goToState('system-info');
            });

            utilityService.hideSpinner();
        };

        /**
         * Fetches the merchant plan and, for FREE/PLUS merchants, reveals the Upgrade button
         * with its benefits tooltip. PREMIUM merchants (or on failure) see nothing.
         */
        const setupUpgrade = () => {
            if (!config.getPromotionalBannerUrl) {
                return;
            }

            ajaxService.get(config.getPromotionalBannerUrl, (response) => {
                if (!response || !response.planTier || response.planTier === 'PREMIUM' || !response.upgradeUrl) {
                    return;
                }

                const wrapper = templateService.getComponent('pl-dashboard-upgrade'),
                    button = templateService.getComponent('pl-dashboard-upgrade-btn'),
                    tooltip = templateService.getComponent('pl-dashboard-upgrade-tooltip');

                if (!wrapper || !button) {
                    return;
                }

                button.addEventListener('click', () => {
                    window.open(response.upgradeUrl, '_blank');
                });

                if (tooltip) {
                    button.addEventListener('mouseenter', () => tooltip.classList.add('pl-visible'));
                    button.addEventListener('mouseleave', () => tooltip.classList.remove('pl-visible'));
                }

                // Benefit keys contain digits, which translateHtml's placeholder regex cannot
                // resolve, so populate the tooltip list here via the translation service.
                const benefits = templateService.getComponent('pl-dashboard-upgrade-benefits');
                if (benefits) {
                    benefits.innerHTML = '';
                    for (let i = 1; i <= 6; i++) {
                        const li = document.createElement('li');
                        li.textContent = translationService.translate('subscription.tooltipBenefit' + i);
                        benefits.appendChild(li);
                    }
                }

                wrapper.classList.remove('pl-hidden');
            });
        };

        /**
         * Displays page content.
         */
        this.display = () => {
            templateService.setCurrentTemplate(templateId);
            const mainPage = templateService.getMainPage(),
                backButton = mainPage.querySelector('.pl-sub-header button');

            backButton.addEventListener('click', () => {
                state.goToState('my-shipping-services');
            });

            mainPage.querySelector('#pl-navigate-order-status').addEventListener('click', () => {
                state.goToState('order-status-mapping');
            });

            let customs = mainPage.querySelector('#pl-navigate-customs')
            if(customs) {
                customs.addEventListener('click', () => {
                    state.goToState('customs')
                })
            }

            mainPage.querySelector('#pl-navigate-warehouse').addEventListener('click', () => {
                state.goToState('default-warehouse', {
                    'code': 'config',
                    'prevState': 'configuration',
                    'nextState': 'configuration',
                });
            });

            mainPage.querySelector('#pl-navigate-parcel').addEventListener('click', () => {
                state.goToState('default-parcel', {
                    'code': 'config',
                    'prevState': 'configuration',
                    'nextState': 'configuration',
                });
            });

            let cod = mainPage.querySelector('#pl-navigate-cod');
            if (cod) {
                cod.addEventListener('click', () => {
                    state.goToState('cash-on-delivery', {
                        'code': 'config',
                        'prevState': 'configuration',
                        'nextState': 'configuration',
                    });
                });
            }

            ajaxService.get(config.getDataUrl, setConfigParams);
            setupUpgrade();
        };
    }

    Packlink.ConfigurationController = ConfigurationController;
})();
