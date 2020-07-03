var Packlink = window.Packlink || {};

(function () {
    /**
     * Main controller of the application.
     *
     * @param {{
     *      pagePlaceholder: string,
     *      pageConfiguration: array,
     *      scrollConfiguration: {scrollOffset: number, rowHeight: number},
     *      hasTaxConfiguration: boolean,
     *      hasCountryConfiguration: boolean,
     *      canDisplayCarrierLogos: boolean,
     *      shippingServiceMaxTitleLength: number,
     *      stateUrl: string,
     *      loginUrl: string,
     *      listOfCountriesUrl: string,
     *      registrationDataUrl: string,
     *      registrationSubmitUrl: string,
     *      getOnboardingStateUrl: string,
     *      autoConfigureStartUrl: string,
     *      dashboardGetStatusUrl: string,
     *      defaultParcelGetUrl: string,
     *      defaultParcelSubmitUrl: string,
     *      defaultWarehouseGetUrl: string,
     *      getSupportedCountriesUrl: string,
     *      defaultWarehouseSubmitUrl: string,
     *      defaultWarehouseSearchPostalCodesUrl: string,
     *      debugGetStatusUrl: string,
     *      debugSetStatusUrl: string,
     *      shippingMethodsGetAllUrl: string,
     *      shippingMethodsGetStatusUrl: string,
     *      shippingMethodsGetTaxClassesUrl: string,
     *      shippingMethodsSaveUrl: string,
     *      shippingMethodsActivateUrl: string,
     *      shippingMethodsDeactivateUrl: string,
     *      shopShippingMethodCountGetUrl: string,
     *      shopShippingMethodsDisableUrl: string,
     *      getSystemOrderStatusesUrl: string,
     *      orderStatusMappingsSaveUrl: string,
     *      orderStatusMappingsGetUrl: string,
     *      getShippingCountriesUrl: string,
     *      logoPath: string,
     *      templates: {}
     * }} configuration
     *
     * @constructor
     */
    function StateController(configuration) {
        let pageControllerFactory = Packlink.pageControllerFactory;
        let ajaxService = Packlink.ajaxService;
        let utilityService = Packlink.utilityService;
        let templateService = Packlink.templateService;
        let context = '';
        let currentState = '';
        let previousState = '';

        let pageConfiguration = {
            'default-parcel': {
                getUrl: configuration.defaultParcelGetUrl,
                submitUrl: configuration.defaultParcelSubmitUrl
            },
            'default-warehouse': {
                getUrl: configuration.defaultWarehouseGetUrl,
                getSupportedCountriesUrl: configuration.getSupportedCountriesUrl,
                submitUrl: configuration.defaultWarehouseSubmitUrl,
                searchPostalCodesUrl: configuration.defaultWarehouseSearchPostalCodesUrl
            },
            'shipping-methods': {
                getDashboardStatusUrl: configuration.dashboardGetStatusUrl,
                getAllMethodsUrl: configuration.shippingMethodsGetAllUrl,
                getMethodsStatusUrl: configuration.shippingMethodsGetStatusUrl,
                activateUrl: configuration.shippingMethodsActivateUrl,
                deactivateUrl: configuration.shippingMethodsDeactivateUrl,
                saveUrl: configuration.shippingMethodsSaveUrl,
                rowHeight: configuration.scrollConfiguration.rowHeight,
                scrollOffset: configuration.scrollConfiguration.scrollOffset,
                maxTitleLength: configuration.shippingServiceMaxTitleLength,
                getShopShippingMethodCountUrl: configuration.shopShippingMethodCountGetUrl,
                disableShopShippingMethodsUrl: configuration.shopShippingMethodsDisableUrl,
                autoConfigureStartUrl: configuration.autoConfigureStartUrl,
                hasTaxConfiguration: configuration.hasTaxConfiguration,
                getTaxClassesUrl: configuration.shippingMethodsGetTaxClassesUrl,
                canDisplayCarrierLogos: configuration.canDisplayCarrierLogos,
                getShippingCountries: configuration.getShippingCountriesUrl,
                hasCountryConfiguration: configuration.hasCountryConfiguration
            },
            'order-state-mapping': {
                getSystemOrderStatusesUrl: configuration.getSystemOrderStatusesUrl,
                getUrl: configuration.orderStatusMappingsGetUrl,
                saveUrl: configuration.orderStatusMappingsSaveUrl
            },
            'footer': {
                getDebugStatusUrl: configuration.debugGetStatusUrl,
                setDebugStatusUrl: configuration.debugSetStatusUrl
            },
            'login': {
                submit: configuration.loginUrl,
                listOfCountriesUrl: configuration.listOfCountriesUrl,
                logoPath: configuration.logoPath
            },
            'register': {
                getRegistrationData: configuration.registrationDataUrl,
                submit: configuration.registrationSubmitUrl
            },
            'onboarding-state': {
                getState: configuration.getOnboardingStateUrl
            },
            'onboarding-overview': {
                defaultParcelGet: configuration.defaultParcelGetUrl,
                defaultWarehouseGet: configuration.defaultWarehouseGetUrl
            }
        };

        if (typeof configuration.pageConfiguration !== 'undefined') {
            pageConfiguration = {...pageConfiguration, ...configuration.pageConfiguration};
        }

        this.display = () => {
            if (configuration.pagePlaceholder) {
                templateService.setMainPlaceholder(configuration.pagePlaceholder);
            }

            templateService.setTemplates(configuration.templates);

            //pageControllerFactory.getInstance('footer', getControllerConfiguration('footer')).display();

            ajaxService.get(configuration.stateUrl, displayPageBasedOnState);
        };

        /**
         * Opens configuration page that corresponds to particular step.
         *
         * @param {string} step
         */
        this.startStep = step => {
            utilityService.disableInputMask();
            let controller = pageControllerFactory.getInstance(step, getControllerConfiguration(step, true));
            controller.display();
        };

        /**
         * Called when configuration step is finished.
         */
        this.stepFinished = () => {
            pageControllerFactory.getInstance(
                'shipping-methods',
                getControllerConfiguration('shipping-methods')).display();
        };

        /**
         * Navigates to a state.
         *
         * @param {string} controller
         * @param {array|null} additionalConfig
         */
        this.goToState = (controller, additionalConfig = null) => {
            let dp = pageControllerFactory.getInstance(
                controller,
                getControllerConfiguration(controller)
            );

            if (dp) {
                dp.display(additionalConfig);
            }

            previousState = currentState;
            currentState = controller;
        }

        this.getPreviousState = () => previousState;

        /**
         * Returns context.
         */
        this.getContext = () => context;

        /**
         * Sets context.
         */
        const setContext = () => {
            context = Math.random().toString(36);
        };

        /**
         * Opens a specific page based on the current state.
         *
         * @param {{state: string}} response
         */
        const displayPageBasedOnState = response => {
            switch (response.state) {
                case 'login':
                    this.goToState('login');
                    break;

                case 'onBoarding':
                    this.goToState('onboarding-state');
                    break;
                default:
                    this.goToState('shipping-methods');
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
            let config = utilityService.cloneObject(pageConfiguration[controller]);

            setContext();
            config.context = context;

            if (fromStep) {
                config.fromStep = true;
            }

            return config;
        };
    }

    Packlink.StateController = StateController;
})();
