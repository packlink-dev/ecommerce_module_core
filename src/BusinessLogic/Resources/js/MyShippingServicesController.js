if (!window.Packlink) {
    window.Packlink = {};
}

(function () {
    /**
     * @param {{getServicesUrl: string, deleteServiceUrl: string, getCurrencyDetailsUrl: string, systemId: string}} configuration
     * @constructor
     */
    function MyShippingServicesController(configuration) {
        const templateService = Packlink.templateService,
            translator = Packlink.translationService,
            ajaxService = Packlink.ajaxService,
            utilityService = Packlink.utilityService,
            state = Packlink.state,
            settingsButtonService = Packlink.settingsButtonService;

        /**
         * @type {ShippingService[]}
         */
        let activeServices = [];

        /**
         * @type SystemInfo
         */
        let systemInfo;

        /**
         * Displays page content.
         *
         */
        this.display = function () {
            utilityService.showSpinner();
            templateService.setCurrentTemplate('pl-my-shipping-services-page');
            ajaxService.get(configuration.getCurrencyDetailsUrl, getDefaultCurrencies);

            const header = templateService.getHeader(),
                settingsMenu = header.querySelector('.pl-configuration-menu'),
                addServiceButtons = document.getElementById('pl-page').querySelectorAll('.pl-add-service-button');

            addServiceButtons.forEach((button) => {
                button.addEventListener('click', addServiceClick);
            });

            settingsButtonService.displaySettings(settingsMenu, state);
        };

        /**
         * Retrieves system info.
         *
         * @param {SystemInfo[]} systemInfos
         */
        const getDefaultCurrencies = (systemInfos) => {
            systemInfo = systemInfos[0];

            if (configuration.systemId !== null) {
                systemInfos.forEach((info) => {
                    if (info.system_id === configuration.systemId) {
                        systemInfo = info;
                    }
                });
            }

            ajaxService.get(configuration.getServicesUrl, bindServices);
        };

        const addServiceClick = () => {
            state.goToState('pick-shipping-service');
        };

        /**
         * Binds services.
         *
         * @param {ShippingService[]} services
         */
        const bindServices = (services) => {
            const table = templateService.getComponent('pl-shipping-services-table'),
                list = templateService.getComponent('pl-shipping-services-list'),
                render = (elem, id, tag) => {
                    Packlink.ShippingServicesRenderer.render(
                        elem,
                        id,
                        tag,
                        activeServices,
                        true,
                        handleServiceAction,
                        systemInfo,
                        'my-shipping-services'
                    );
                };

            activeServices = services;
            render(table.querySelector('tbody'), 'pl-shipping-services-row', 'tr');
            render(list.querySelector('.pl-shipping-services-list'), 'pl-shipping-services-list-item', 'div');

            if (services.length !== 0) {
                // noinspection JSCheckFunctionSignatures
                Packlink.GridResizerService.init(table);
                utilityService.showElement(table);
                utilityService.showElement(list);
                utilityService.hideElement(templateService.getComponent('pl-no-shipping-services'));
            } else {
                utilityService.hideElement(table);
                utilityService.hideElement(list);
                utilityService.showElement(templateService.getComponent('pl-no-shipping-services'));
            }

            utilityService.hideSpinner();
        };

        /**
         * Handles a shipping service action button click.
         *
         * @param {string} serviceId
         * @param {'edit'|'delete'} action
         */
        const handleServiceAction = (serviceId, action) => {
            if (action === 'edit') {
                state.goToState('edit-service', {id: serviceId});
            } else {
                ajaxService.post(configuration.deleteServiceUrl, {id: serviceId}, () => {
                    const filteredServices = activeServices.filter((service) => service.id !== serviceId);
                    bindServices(filteredServices);
                });

                showMessageModal(
                    translator.translate('shippingServices.deletedSuccessTitle'),
                    translator.translate('shippingServices.deletedSuccessDescription')
                );
            }
        };

        const showMessageModal = (title, message) => {
            const modal = new Packlink.modalService({
                content: templateService.replaceResourcesUrl(
                    '<div class="pl-center pl-separate-horizontally">' +
                    '<img src="{$BASE_URL$}/images/checklist.png" alt="">' +
                    '<h1 class="pl-modal-title pl-no-margin">' + title + '</h1>' +
                    '<p class="pl-modal-subtitle pl-separate-vertically">' + message + '</p>' +
                    '</div>'
                )
            });

            modal.open();
        };
    }

    Packlink.MyShippingServicesController = MyShippingServicesController;
})();
