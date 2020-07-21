if (!window.Packlink) {
    window.Packlink = {};
}

(function () {
    /**
     * @param {{getServicesUrl: string, deleteServiceUrl: string, [disableCarriersUrl]: string}} configuration
     * @constructor
     */
    function MyShippingServicesController(configuration) {
        const templateService = Packlink.templateService,
            translator = Packlink.translationService,
            ajaxService = Packlink.ajaxService,
            utilityService = Packlink.utilityService,
            state = Packlink.state;

        /**
         * @type {ShippingService[]}
         */
        let activeServices = [];

        /**
         * Displays page content.
         *
         * @param {{from: string, newService: boolean}} config
         */
        this.display = function (config) {
            utilityService.showSpinner();
            templateService.setCurrentTemplate('pl-my-shipping-services-page');
            ajaxService.get(configuration.getServicesUrl, (response) => {
                bindServices(response, config);
            });

            const header = templateService.getHeader(),
                settingsMenu = header.querySelector('.pl-configuration-menu'),
                addServiceButtons = document.getElementById('pl-page').querySelectorAll('.pl-add-service-button');

            addServiceButtons.forEach((button) => {
                button.addEventListener('click', addServiceClick);
            });

            settingsMenu.addEventListener('click', () => {
                state.goToState('configuration');
            });
        };

        const addServiceClick = () => {
            state.goToState('pick-shipping-service');
        };

        /**
         * Binds services.
         *
         * @param {ShippingService[]} services
         * @param {{from: string, newService: boolean}} [config]
         */
        const bindServices = (services, config) => {
            const table = templateService.getComponent('pl-shipping-services-table'),
                list = templateService.getComponent('pl-shipping-services-list'),
                render = (elem, id, tag) => {
                    Packlink.ShippingServicesRenderer.render(elem, id, tag, activeServices, true, handleServiceAction);
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

            if (config && config.from === 'edit') {
                if (config.newService === true && services.length === 1 && configuration.disableCarriersUrl) {
                    const modal = new Packlink.modalService({
                        canClose: false,
                        content: templateService.getTemplate('pl-disable-carriers-modal'),
                        buttons: [
                            {
                                title: translator.translate('general.accept'),
                                cssClasses: ['pl-button-primary'],
                                onClick: () => {
                                    ajaxService.post(configuration.disableCarriersUrl, {}, modal.close, Packlink.responseService.errorHandler);
                                }
                            },
                            {
                                title: translator.translate('general.cancel'),
                                cssClasses: ['pl-button-secondary'],
                                onClick: () => {
                                    modal.close();
                                }
                            }
                        ]
                    });

                    modal.open();
                } else {
                    showMessageModal(
                        translator.translate('shippingServices.addedSuccessTitle'),
                        translator.translate('shippingServices.addedSuccessDescription')
                    );
                }
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
                    '<img src="{$BASE_URL$}/images/checklist.png" alt="" class="pl-bottom-separate">' +
                    '<h1 class="pl-modal-title pl-no-margin">' + title + '</h1>' +
                    '<p class="pl-modal-subtitle pl-top-separate">' + message + '</p>' +
                    '</div>'
                )
            });

            modal.open();
        };
    }

    Packlink.MyShippingServicesController = MyShippingServicesController;
})();
