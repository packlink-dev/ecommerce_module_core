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

            ajaxService.get(configuration.stateUrl, displayPageBasedOnState);
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
