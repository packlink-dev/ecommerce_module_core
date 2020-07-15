if (!window.Packlink) {
    window.Packlink = {};
}

(function () {
    function MyShippingServicesController() {
        const templateService = Packlink.templateService,
            state = Packlink.state;

        /**
         * Displays page content.
         */
        this.display = function () {
            templateService.setCurrentTemplate('pl-my-shipping-services-page');
            const header = templateService.getHeader(),
                settingsMenu = header.querySelector('.pl-configuration-menu'),
                addServiceButton = header.querySelector('button');

            addServiceButton.addEventListener('click', () => {
                state.goToState('pick-shipping-service');
            });

            settingsMenu.addEventListener('click', () => {
                state.goToState('configuration');
            });
        };
    }

    Packlink.MyShippingServicesController = MyShippingServicesController;
})();
