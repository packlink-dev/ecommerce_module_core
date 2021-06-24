if (!window.Packlink) {
    window.Packlink = {};
}

(function () {
    /**
     * @typedef PickShippingServiceControllerConfiguration
     * @property {string} getServicesUrl
     * @property {string} getActiveServicesUrl
     * @property {string} getTaskStatusUrl
     * @property {string} startAutoConfigureUrl
     * @property {string} disableCarriersUrl
     * @property {string} getCurrencyDetailsUrl
     * @property {string} systemId
     * @property {boolean} newService
     */

    /**
     * @param {PickShippingServiceControllerConfiguration} configuration
     * @constructor
     */
    function PickShippingServiceController(configuration) {

        const templateService = Packlink.templateService,
            ajaxService = Packlink.ajaxService,
            translator = Packlink.translationService,
            utilityService = Packlink.utilityService,
            state = Packlink.state,
            templateId = 'pl-pick-service-page';

        let appliedFilter = {};
        /**
         * @type ShippingService[]
         */
        let shippingServices;
        let desktopMode = true;
        /**
         * @type ModalService
         */
        let noServicesModal;

        /**
         * @type SystemInfo
         */
        let systemInfo;

        /**
         * Displays page content.
         *
         *  @param {{from: string, newService: boolean}} config
         */
        this.display = function (config) {
            utilityService.showSpinner();
            templateService.setCurrentTemplate(templateId);
            ajaxService.get(configuration.getCurrencyDetailsUrl, (response) => {
                getDefaultCurrencies(response, config);
            });

            const mainPage = templateService.getMainPage(),
                backButton = mainPage.querySelector('.pl-sub-header button');

            backButton.addEventListener('click', () => {
                state.goToState('my-shipping-services');
            });

            mainPage.querySelectorAll('.pl-filter-option').forEach((optionBtn) => {
                optionBtn.addEventListener('click', () => {
                    desktopMode = true;
                    filterButtonClicked(optionBtn);
                });
            });

            mainPage.querySelector('#pl-open-filter-button').addEventListener('click', showFilterModal);
        };

        /**
         * Checks the status of the update shipping services task.
         *
         * @param {{status: string}} response
         * @param {{from: string, newService: boolean}} config
         */
        const checkServicesStatus = (response, config) => {
            if (response.status === 'completed') {
                ajaxService.get(configuration.getServicesUrl, (services) => {
                    bindServices(services, config);
                });
            } else if (response.status === 'failed') {
                showNoServicesModal();
            } else {
                setTimeout(
                    function () {
                        ajaxService.get(configuration.getTaskStatusUrl, (res) => {
                            checkServicesStatus(res, config);
                        });
                    },
                    1000
                );
            }
        };

        /**
         * Shows the modal with the no services message.
         */
        const showNoServicesModal = () => {
            utilityService.hideSpinner();
            if (!noServicesModal) {
                noServicesModal = new Packlink.modalService({
                    title: translator.translate('shippingServices.failedGettingServicesTitle'),
                    content: '<p class="pl-modal-subtitle">' + translator.translate('shippingServices.failedGettingServicesSubtitle') + '</p>',
                    canClose: false,
                    buttons: [
                        {
                            title: translator.translate('shippingServices.retry'),
                            primary: true,
                            onClick: () => {
                                startAutoConfigure();
                            }
                        },
                        {
                            title: translator.translate('general.cancel'),
                            onClick: () => {
                                hideNoServicesModal();
                                state.goToState('my-shipping-services');
                            }
                        },
                    ]
                });
            }

            noServicesModal.open();
        };

        /**
         * Shows the block with the no services message.
         */
        const hideNoServicesModal = () => {
            noServicesModal.close();
        };

        /**
         * Starts the auto-configure process.
         */
        const startAutoConfigure = () => {
            hideNoServicesModal();
            utilityService.showSpinner();
            ajaxService.get(
                configuration.startAutoConfigureUrl,
                (response) => {
                    if (response.success) {
                        hideNoServicesModal();
                        ajaxService.get(configuration.getTaskStatusUrl, checkServicesStatus);
                    } else {
                        showNoServicesModal();
                    }
                },
                showNoServicesModal
            );
        };

        /**
         * Retrieves system info.
         *
         * @param {SystemInfo[]} systemInfos
         * @param {{from: string, newService: boolean}} config
         */
        const getDefaultCurrencies = (systemInfos, config) => {
            systemInfo = systemInfos[0];

            if (configuration.systemId !== null) {
                systemInfos.forEach((info) => {
                    if (info.system_id === configuration.systemId) {
                        systemInfo = info;
                    }
                });
            }

            ajaxService.get(configuration.getTaskStatusUrl, (response) => {
                checkServicesStatus(response, config);
            });
        };

        /**
         * Binds services.
         *
         * @param {ShippingService[]} services
         * @param {{from: string, newService: boolean}} config
         */
        const bindServices = (services, config) => {
            shippingServices = services;
            applyFilter();

            ajaxService.get(configuration.getActiveServicesUrl, (activeServices) => {
                if (config && config.from === 'edit') {
                    if (config.newService === true && activeServices.length === 1 && configuration.disableCarriersUrl) {
                        displayDisableShopServicesModal();
                    } else {
                        const modal = new Packlink.modalService({
                            content: templateService.replaceResourcesUrl(
                                '<div class="pl-center pl-separate-horizontally">' +
                                '<img src="{$BASE_URL$}/images/checklist.png" alt="">' +
                                '<h1 class="pl-modal-title pl-no-margin">' +
                                translator.translate('shippingServices.addedSuccessTitle') +
                                '</h1>' +
                                '<p class="pl-modal-subtitle pl-separate-vertically">' +
                                translator.translate('shippingServices.addedSuccessDescription')
                                + '</p>' +
                                '</div>'
                            )
                        });

                        modal.open();
                    }
                }

                utilityService.hideSpinner();
            });
        };

        /**
         * Filters services based on the selected filter.
         */
        const applyFilter = () => {
            setSelectedFiltersToPage();
            const filteredServices = filterServices(),
                table = templateService.getComponent('pl-shipping-services-table'),
                list = templateService.getComponent('pl-shipping-services-list').querySelector('.pl-shipping-services-list'),
                render = (elem, id, tag) => {
                    Packlink.ShippingServicesRenderer.render(
                        elem,
                        id,
                        tag,
                        filteredServices,
                        false,
                        handleServiceAction,
                        systemInfo,
                        'pick-shipping-services'
                    );
                };

            render(table.querySelector('tbody'), 'pl-shipping-services-row', 'tr');
            render(list, 'pl-shipping-services-list-item', 'div');
            // noinspection JSCheckFunctionSignatures
            Packlink.GridResizerService.init(table);
        };

        /**
         * Handles a shipping service action button click.
         *
         * @param {string} serviceId
         */
        const handleServiceAction = (serviceId) => {
            state.goToState('edit-service', {id: serviceId, fromPick: true});
        };

        /**
         * Applies the filter to the list of services.
         *
         * @return {ShippingService[]}
         */
        const filterServices = () => {
            return shippingServices.filter((service) => {
                for (const filter in appliedFilter) {
                    if (appliedFilter.hasOwnProperty(filter) && appliedFilter[filter]
                        && service.hasOwnProperty(filter) && service[filter] !== appliedFilter[filter]
                    ) {
                        return false;
                    }
                }

                return true;
            });
        };

        const showFilterModal = () => {
            // noinspection JSCheckFunctionSignatures
            const modal = new Packlink.modalService({
                title: translator.translate('shippingServices.filterModalTitle'),
                content: '<div class="pl-filters-wrapper">' + templateService.getMainPage().querySelector('.pl-services-filter-wrapper').innerHTML + '</div>',
                buttons: [
                    {
                        title: translator.translate('shippingServices.applyFilters'),
                        primary: true,
                        onClick: (event) => {
                            applyModalFilter(event.target.parentElement.parentElement);
                            modal.close();
                        }
                    }
                ],
                onOpen: filterModalOpened
            });

            desktopMode = false;
            modal.open();
        };

        /**
         * Handles an event when filter modal is opened.
         *
         * @param {HTMLElement} modal
         */
        const filterModalOpened = (modal) => {
            modal.querySelectorAll('.pl-filter-option').forEach((btn) => {
                setButtonFromFilter(btn);
                btn.addEventListener('click', () => filterButtonClicked(btn));
            });

            modal.querySelector('.pl-filter-selected').remove();
        };

        /**
         * Sets the filter option from the given button.
         *
         * @param {HTMLDivElement} btn
         */
        const setFilterFromButton = (btn) => {
            const parent = btn.parentElement,
                otherBtn = parent.querySelector(':not([data-option=' + btn.dataset.option + '])');

            if (btn.classList.contains('pl-selected')) {
                appliedFilter[btn.dataset.filter] = btn.dataset.option;
            } else if (otherBtn.classList.contains('pl-selected')) {
                appliedFilter[btn.dataset.filter] = otherBtn.dataset.option;
            } else {
                appliedFilter[btn.dataset.filter] = null;
            }
        };

        /**
         * Sets the button class from the filter.
         *
         * @param {HTMLDivElement} btn
         */
        const setButtonFromFilter = (btn) => {
            if (appliedFilter[btn.dataset.filter] === btn.dataset.option) {
                btn.classList.add('pl-selected');
            } else {
                btn.classList.remove('pl-selected');
            }
        };

        /**
         * When modal is closed, display selected filter options above the services table.
         */
        const setSelectedFiltersToPage = () => {
            const placeholder = templateService.getMainPage().querySelector('.pl-services-filter-wrapper .pl-filter-selected'),
                elem = placeholder.querySelector('.pl-filter-options');

            elem.innerHTML = '';
            placeholder.classList.add('pl-hidden');

            Object.keys(appliedFilter).forEach((filterType) => {
                if (appliedFilter[filterType]) {
                    const newDiv = document.createElement('div');
                    newDiv.classList.add('pl-filter-option');
                    newDiv.classList.add('pl-selected');
                    newDiv.dataset.filter = filterType;
                    newDiv.dataset.option = appliedFilter[filterType];
                    newDiv.innerHTML = translator.translate('shippingServices.' + appliedFilter[filterType]);
                    newDiv.addEventListener('click', () => {
                        appliedFilter[filterType] = null;
                        newDiv.remove();
                        setPageFilters();
                        applyFilter();
                    });

                    elem.appendChild(newDiv);
                    placeholder.classList.remove('pl-hidden');
                }
            });
        };

        /**
         * Applies the filter from the modal.
         *
         * @param {HTMLElement} modal
         */
        const applyModalFilter = (modal) => {
            modal.querySelectorAll('.pl-filter-option').forEach(setFilterFromButton);
            setPageFilters();

            applyFilter();
        };

        /**
         * Sets the state of page filters from the applied filters.
         */
        const setPageFilters = () => {
            const pageFilters = templateService.getMainPage().querySelectorAll('.pl-services-filter-wrapper .pl-filter-option');
            pageFilters.forEach((btn) => {
                btn.classList.add('pl-selected');
                setButtonFromFilter(btn);
            });
        };

        /**
         * Handles click on a filter button.
         *
         * @param {HTMLDivElement} btn
         */
        const filterButtonClicked = (btn) => {
            const filter = btn.dataset.option;

            btn.parentElement.querySelector(':not([data-option=' + filter + '])').classList.remove('pl-selected');
            btn.classList.toggle('pl-selected');

            if (desktopMode) {
                setFilterFromButton(btn);
                applyFilter();
            }
        };

        /**
         * Displays the modal for disabling shop shipping services.
         */
        const displayDisableShopServicesModal = () => {
            const modal = new Packlink.modalService({
                canClose: false,
                content: templateService.getTemplate('pl-disable-carriers-modal'),
                buttons: [
                    {
                        title: translator.translate('general.accept'),
                        primary: true,
                        onClick: () => {
                            utilityService.showSpinner();
                            ajaxService.post(
                                configuration.disableCarriersUrl,
                                {},
                                () => {
                                    utilityService.hideSpinner();
                                    modal.close();
                                },
                                Packlink.responseService.errorHandler
                            );
                        }
                    },
                    {
                        title: translator.translate('general.cancel'),
                        onClick: () => {
                            modal.close();
                        }
                    }
                ]
            });

            modal.open();
        };
    }

    Packlink.PickShippingServiceController = PickShippingServiceController;
})();
