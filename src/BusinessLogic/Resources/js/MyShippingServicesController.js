if (!window.Packlink) {
    window.Packlink = {};
}

(function () {
    /**
     * @param {{getServicesUrl: string, deleteServiceUrl: string, getCurrencyDetailsUrl: string, systemId: string,
     *     getSubscriptionPlanUrl?: string, getPromotionalBannerUrl?: string}} configuration
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
         * Simulated prices sidebar; null when the module does not provide the panel URLs.
         *
         * @type {{open: function, close: function}|null}
         */
        let ratesSidebar = null;

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
            ratesSidebar = initRatesSidebar();

            if (configuration.getSubscriptionPlanUrl) {
                const bannerController = new Packlink.SubscriptionBannerController();
                bannerController.init({
                    getSubscriptionPlanUrl: configuration.getSubscriptionPlanUrl,
                    getPromotionalBannerUrl: configuration.getPromotionalBannerUrl
                });
            }
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

        /**
         * Adds the "Discover our rates" button and the simulated prices sidebar (CR-SET-72-02) when the module
         * provides the panel URLs. The panel loads its data, and stays hidden for PREMIUM merchants, only once
         * the sidebar is opened.
         *
         * @return {{open: function, close: function}|null}
         */
        const initRatesSidebar = () => {
            const panel = Packlink.SimulatedPricesPanel
                ? Packlink.SimulatedPricesPanel.fromPageConfiguration(configuration)
                : null;
            const page = templateService.getMainPage().querySelector('.pl-my-shipping-services-page');
            if (!panel || !page) {
                return null;
            }

            const header = page.querySelector('header'),
                content = page.querySelector('.pl-page-content'),
                main = document.createElement('div'),
                sidebar = document.createElement('aside'),
                button = document.createElement('button');

            main.className = 'pl-simulated-prices-main';
            while (content.firstChild) {
                main.appendChild(content.firstChild);
            }

            content.appendChild(main);
            sidebar.className = 'pl-simulated-prices-sidebar pl-hidden';
            content.appendChild(sidebar);

            let opened = false;
            const sidebarApi = {
                open: () => {
                    if (opened) {
                        return;
                    }

                    opened = true;
                    utilityService.showElement(sidebar);
                    page.classList.add('pl-simulated-prices-open');
                    button.setAttribute('aria-expanded', 'true');
                    panel.render(sidebar, {onClose: sidebarApi.close});
                    window.dispatchEvent(new Event('resize'));
                },
                close: () => {
                    opened = false;
                    utilityService.hideElement(sidebar);
                    page.classList.remove('pl-simulated-prices-open');
                    button.setAttribute('aria-expanded', 'false');
                    window.dispatchEvent(new Event('resize'));
                }
            };

            button.type = 'button';
            button.className = 'pl-button pl-button-inverted pl-discover-rates-button';
            button.setAttribute('aria-expanded', 'false');
            button.innerHTML = '<i class="material-icons">local_shipping</i>';
            button.appendChild(document.createTextNode(translator.translate('simulatedPrices.discoverRates')));
            button.addEventListener('click', () => (opened ? sidebarApi.close() : sidebarApi.open()));
            header.appendChild(button);

            return sidebarApi;
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
                if (ratesSidebar) {
                    ratesSidebar.open();
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
