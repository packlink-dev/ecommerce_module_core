if (!window.Packlink) {
    window.Packlink = {};
}

(function () {
    /**
     * @typedef StateConfiguration
     * @property {string} [pagePlaceholder]
     * @property {{}} pageConfiguration
     * @property {string} stateUrl
     * @property {string} systemId
     * @property {{}} templates
     * @property {string} baseResourcesUrl
     */

    /**
     * Main controller of the application.
     *
     * @param {StateConfiguration} configuration
     *
     * @constructor
     */
    function StateController(configuration) {
        const pageControllerFactory = Packlink.pageControllerFactory,
            ajaxService = Packlink.ajaxService,
            utilityService = Packlink.utilityService,
            templateService = Packlink.templateService;

        let currentState = '';
        let previousState = '';

        this.display = () => {
            templateService.setBaseResourceUrl(configuration.baseResourcesUrl);
            if (configuration.pagePlaceholder) {
                templateService.setMainPlaceholder(configuration.pagePlaceholder);
            }

            templateService.setTemplates(configuration.templates);

            //ajaxService.get(configuration.stateUrl, displayPageBasedOnState);
            ajaxService.get(configuration.stateUrl, (response) => {
                displayPageBasedOnState(response);

                ajaxService.get(configuration.integrationStatusUrl, (statusResponse) => {
                    if (statusResponse.status === 'DISABLED') {
                        displayIntegrationDisabledPopup();
                    }
                });
            });
        };

        /**
         * Navigates to a state.
         *
         * @param {string} controller
         * @param {object|null} additionalConfig
         */
        this.goToState = (controller, additionalConfig = null) => {
            Packlink.StateUUIDService.setStateUUID(Math.random().toString(36));

            let dp = pageControllerFactory.getInstance(
                controller,
                getControllerConfiguration(controller)
            );

            if (dp) {
                utilityService.showSpinner();
                dp.display(additionalConfig);
            }

            previousState = currentState;
            currentState = controller;
        };

        this.getPreviousState = () => previousState;

        /**
         * Opens a specific page based on the current state.
         *
         * @param {{state: string}} response
         */
        const displayPageBasedOnState = (response) => {
            switch (response.state) {
                case 'login':
                    this.goToState('login');
                    break;
                case 'onBoarding':
                    this.goToState('onboarding-state');
                    break;
                default:
                    this.goToState('my-shipping-services');
                    break;
            }
        };

        /**
         * Displays the integration disabled popup overlay.
         * Shown when Packlink has deactivated this store's integration,
         * e.g. due to subscription plan limits.
         */
        const displayIntegrationDisabledPopup = () => {
            const existing = document.getElementById('pl-integration-disabled-overlay');
            if (existing) {
                existing.parentNode.removeChild(existing);
            }

            const resubscribeUrl = 'https://pro.packlink.' + configuration.platformDomain
                + '/private/subscriptions';

            const overlay = document.createElement('div');
            overlay.id = 'pl-integration-disabled-overlay';
            overlay.style.cssText = [
                'position: absolute',
                'top: 0',
                'left: 0',
                'width: 100%',
                'height: 100%',
                'background: rgba(0,0,0,0.30)',
                'z-index: 9999',
                'display: flex',
                'align-items: center',
                'justify-content: center',
            ].join(';');

            overlay.innerHTML = [
                '<div style="background:#fff;max-width:560px;width:100%;padding:70px 15px;',
                '            border-radius:4px;font-family:sans-serif;',
                '            box-shadow:0 2px 16px rgba(0,0,0,0.15);">',

                '  <h2 style="margin:0 0 35px 0;font-size:20px;color:#184e87;font-weight:600;text-align:center;">',
                '    Your module has been disabled',
                '  </h2>',

                '  <div style="max-width:420px;margin:0 auto;text-align:left;color:#333;line-height:1.6;">',

                '    <p style="margin-bottom:14px;">',
                '      Your Packlink PRO integration has been disabled for this store',
                '    </p>',

                '    <p style="margin-bottom:14px;">',
                '      Your Packlink PRO integration has been disabled for this store<br>',
                '      because you have exceeded the number of store connections<br>',
                '      included in your subscription plan.',
                '    </p>',

                '    <p style="margin-bottom:14px;">',
                '      While the integration is disabled, you will not be able to use<br>',
                '      Packlink shipping services, including dynamic rates and<br>',
                '      shipment creation and management.',
                '    </p>',

                '    <p style="margin:0;">',
                '      To automatically reactivate the integration and regain access to<br>',
                '      all features, please resubscribe to the appropriate subscription<br>',
                '      plan <a href="' + resubscribeUrl + '" target="_blank" style="color:#0076ff;">here</a>.',
                '    </p>',

                '  </div>',
                '</div>',
                '</div>'
            ].join('');

            const plPage = document.getElementById('pl-page');
            plPage.style.position = 'relative';
            plPage.style.overflow = 'hidden';
            plPage.appendChild(overlay);
        };

        /**
         * Gets controller configuration.
         *
         * @param {string} controller
         * @param {boolean} [fromStep]
         * @return {{}}
         */
        const getControllerConfiguration = (controller, fromStep = false) => {
            let config = utilityService.cloneObject(configuration.pageConfiguration[controller]);

            if (fromStep) {
                config.fromStep = true;
            }

            return config;
        };
    }

    Packlink.StateController = StateController;
})();
