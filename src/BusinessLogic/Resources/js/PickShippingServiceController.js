if (!window.Packlink) {
    window.Packlink = {};
}

(function () {
    /**
     * @param {{getServicesUrl: string}} configuration
     * @constructor
     */
    function PickShippingServiceController(configuration) {

        const templateService = Packlink.templateService,
            ajaxService = Packlink.ajaxService,
            translationService = Packlink.translationService,
            state = Packlink.state,
            templateId = 'pl-pick-service-page';

        let appliedFilter = {};
        /**
         * @type ShippingService[]
         */
        let shippingServices;
        let desktopMode = true;

        /**
         * Displays page content.
         */
        this.display = function () {
            templateService.setCurrentTemplate(templateId);
            ajaxService.get(configuration.getServicesUrl, bindServices);

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
         * Binds services.
         *
         * @param {ShippingService[]} services
         */
        const bindServices = (services) => {
            shippingServices = services;
            applyFilter();
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
                    Packlink.ShippingServicesRenderer.render(elem, id, tag, filteredServices, false, handleServiceAction);
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
            state.goToState('edit-service', {id: serviceId});
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
                title: translationService.translate('shippingServices.filterModalTitle'),
                content: templateService.getMainPage().querySelector('.pl-services-filter-wrapper').innerHTML,
                buttons: [
                    {
                        title: translationService.translate('shippingServices.applyFilters'),
                        cssClasses: ['pl-button-primary'],
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
                    newDiv.innerHTML = translationService.translate('shippingServices.' + filterType).toUpperCase()
                        + ': ' + translationService.translate('shippingServices.' + appliedFilter[filterType]);
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
    }

    Packlink.PickShippingServiceController = PickShippingServiceController;
})();
