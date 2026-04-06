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
            const resubscribeUrl = 'https://pro.packlink.' + configuration.platformDomain
                + '/private/subscriptions';

            const resubscribeLinkText = Packlink.translationService.translate('integrationDisabled.resubscribeLink');
            const resubscribeText = Packlink.translationService.translate(
                'integrationDisabled.resubscribe',
                ['<a href="' + resubscribeUrl + '" target="_blank" class="pl-modal-link">' + resubscribeLinkText + '</a>']
            );

            const content = [
                '<div class="pl-integration-disabled-content">',
                '  <p>' + Packlink.translationService.translate('integrationDisabled.storeDisabled') + '</p>',
                '  <p>' + Packlink.translationService.translate('integrationDisabled.exceededConnections') + '</p>',
                '  <p>' + Packlink.translationService.translate('integrationDisabled.whileDisabled') + '</p>',
                '  <p>' + resubscribeText + '</p>',
                '</div>'
            ].join('');

            const modal = new Packlink.modalService({
                title: Packlink.translationService.translate('integrationDisabled.title'),
                content: content,
                canClose: true,
                footer: false,
                onOpen: (modalElement) => {
                    modalElement.querySelector('.pl-modal').classList.add('pl-integration-disabled-modal');
                }
            });

            modal.open();
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