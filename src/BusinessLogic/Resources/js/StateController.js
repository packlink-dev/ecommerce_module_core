var Packlink = window.Packlink || {};

(function () {
    function StateController(configuration) {
        let pageControllerFactory = Packlink.pageControllerFactory;
        let sidebarController = new Packlink.SidebarController(navigate);
        let utilityService = Packlink.utilityService;
        let context = '';

        let pageConfiguration = {
            'default-parcel': {
                getUrl: configuration.defaultParcelGetUrl,
                submitUrl: configuration.defaultParcelSubmitUrl
            },
            'default-warehouse': {
                getUrl: configuration.defaultWarehouseGetUrl,
                submitUrl: configuration.defaultWarehouseSubmitUrl
            },
            'shipping-methods': {
                getStatusUrl: configuration.dashboardGetStatusUrl,
                getAllUrl: configuration.shippingMethodsGetAllUrl,
                activateUrl: configuration.shippingMethodsActivateUrl,
                deactivateUrl: configuration.shippingMethodsDeactivateUrl,
                saveUrl: configuration.shippingMethodsSaveUrl,
                rowHeight: configuration.scrollConfiguration.rowHeight,
                scrollOffset: configuration.scrollConfiguration.scrollOffset,
                maxTitleLength: configuration.shippingServiceMaxTitleLength,
                getShopShippingMethodCountUrl: configuration.shopShippingMethodCountGetUrl,
                disableShopShippingMethodsUrl: configuration.shopShippingMethodsDisableUrl,
                hasTaxConfiguration: configuration.hasTaxConfiguration,
                getTaxClassesUrl: configuration.shippingMethodsGetTaxClasses,
            },
            'order-state-mapping': {
                getSystemOrderStatusesUrl: configuration.getSystemOrderStatusesUrl,
                getUrl: configuration.orderStatusMappingsGetUrl,
                saveUrl: configuration.orderStatusMappingsSaveUrl
            },
            'footer': {
                getStatusUrl: configuration.debugGetStatusUrl,
                setStatusUrl: configuration.debugSetStatusUrl
            }
        };
        this.startStep = startStep;
        this.stepFinished = stepFinished;
        this.display = display;
        this.getContext = getContext;

        function display() {
            pageControllerFactory.getInstance('footer', getControllerConfiguration('footer')).display();

            let dp = pageControllerFactory.getInstance(
                'shipping-methods',
                getControllerConfiguration('shipping-methods')
            );
            dp.display();
        }

        /**
         * Navigation callback.
         * Handles navigation menu button clicked event.
         *
         * @param event
         */
        function navigate(event) {
            let state = event.target.getAttribute('data-pl-sidebar-btn');
            sidebarController.setState(state);
            if (state !== 'basic-settings') {
                utilityService.disableInputMask();

                let controller = pageControllerFactory.getInstance(state, getControllerConfiguration(state));
                controller.display();
            }
        }

        /**
         * Opens configuration page that corresponds to particular step.
         *
         * @param {string} step
         */
        function startStep(step) {
            utilityService.disableInputMask();
            let controller = pageControllerFactory.getInstance(step, getControllerConfiguration(step, true));
            controller.display();
        }

        /**
         * Called when configuration step is finished.
         */
        function stepFinished() {
            pageControllerFactory.getInstance(
                'shipping-methods',
                getControllerConfiguration('shipping-methods')).display();
        }

        function getControllerConfiguration(controller, fromStep) {
            if (typeof fromStep === "undefined") {
                fromStep = false;
            }

            let config = utilityService.cloneObject(pageConfiguration[controller]);

            setContext();
            config.context = context;

            if (fromStep) {
                config.fromStep = true;
            }

            return config;
        }

        /**
         * Sets context.
         */
        function setContext() {
            context = Math.random().toString(36);
        }

        /**
         * Returns context.
         */
        function getContext() {
            return context;
        }
    }

    Packlink.StateController = StateController;
})();