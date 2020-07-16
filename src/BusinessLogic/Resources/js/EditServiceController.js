if (!window.Packlink) {
    window.Packlink = {};
}

(function () {
    /**
     * @typedef EditServiceControllerConfiguration
     * @property {string} getServiceUrl
     * @property {boolean} hasTaxConfiguration
     * @property {boolean} hasCountryConfiguration
     * @property {boolean} canDisplayCarrierLogos
     */

    /**
     * @param {EditServiceControllerConfiguration} configuration
     * @constructor
     */
    function EditServiceController(configuration) {
        const templateService = Packlink.templateService,
            ajaxService = Packlink.ajaxService,
            state = Packlink.state,
            templateId = 'pl-edit-service-page';

        /**
         * Displays page content.
         *
         * @param {{id: string}} config
         */
        this.display = function (config) {
            templateService.setCurrentTemplate(templateId);
            ajaxService.get(configuration.getServiceUrl + '&id=' + config.id, bindService);

            const mainPage = templateService.getMainPage(),
                backButton = mainPage.querySelector('.pl-sub-header button');

            backButton.addEventListener('click', () => {
                state.goToState('my-shipping-services');
            });
        };

        /**
         * Binds service.
         *
         * @param {ShippingService} service
         */
        const bindService = (service) => {
            console.log(service);
        };
    }

    Packlink.EditServiceController = EditServiceController;
})();
