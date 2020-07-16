if (!window.Packlink) {
    window.Packlink = {};
}

(function () {
    /**
     * @typedef EditServiceControllerConfiguration
     * @property {string} getServiceUrl
     * @property {string} saveServiceUrl
     * @property {string} getTaxClassesUrl
     * @property {boolean} hasTaxConfiguration
     * @property {boolean} hasCountryConfiguration
     * @property {boolean} canDisplayCarrierLogos
     * @property {int} [maxTitleLength]
     */

    /**
     * @param {EditServiceControllerConfiguration} configuration
     * @constructor
     */
    function EditServiceController(configuration) {
        const templateService = Packlink.templateService,
            ajaxService = Packlink.ajaxService,
            utilityService = Packlink.utilityService,
            translator = Packlink.translationService,
            validationService = Packlink.validationService,
            state = Packlink.state,
            templateId = 'pl-edit-service-page';

        /**
         * @type ShippingService
         */
        let serviceModel = {};

        const modelFields = [
            'name',
            'showLogo',
            'tax',
        ];

        /**
         * Displays page content.
         *
         * @param {{id: string}} config
         */
        this.display = function (config) {
            templateService.setCurrentTemplate(templateId);
            ajaxService.get(configuration.getServiceUrl + '&id=' + config.id, bindService);

            const mainPage = templateService.getMainPage(),
                backButton = mainPage.querySelector('.pl-sub-header button'),
                policySwitchButton = templateService.getComponent('pl-configure-prices-button');

            backButton.addEventListener('click', () => {
                state.goToState('my-shipping-services');
            });

            policySwitchButton.addEventListener('click', () => {
                policySwitchButton.classList.toggle('pl-selected');
                handlePolicySwitchButton(policySwitchButton);
            });

            handlePolicySwitchButton(policySwitchButton);
        };

        /**
         * Binds service.
         *
         * @param {ShippingService} service
         */
        const bindService = (service) => {
            const form = templateService.getComponent('pl-edit-service-form');
            serviceModel = service;

            for (const field of modelFields) {
                let input = form[field];
                input.addEventListener('blur', (event) => {
                    // noinspection JSCheckFunctionSignatures
                    validationService.validateInputField(event.target);
                }, true);
                input.addEventListener('input', (event) => {
                    // noinspection JSCheckFunctionSignatures
                    validationService.removeError(event.target);
                }, true);
            }

            templateService.getComponent('pl-service-title').value = service.name;

            if (configuration.canDisplayCarrierLogos) {
                const showLogoBtn = templateService.getComponent('pl-show-logo');

                utilityService.showElement(templateService.getComponent('pl-show-logo-group'));
                showLogoBtn.checked = service.showLogo;
            }

            if (configuration.hasTaxConfiguration) {
                utilityService.showElement(templateService.getComponent('pl-tax-class-section'));
                ajaxService.get(configuration.getTaxClassesUrl, populateTaxClasses);
            }

            if (configuration.hasCountryConfiguration) {
                setCountrySelection();
            }

            templateService.getComponent('pl-page-submit-btn').addEventListener('click', save);

            utilityService.hideSpinner();
        };

        /**
         * Fills tax select box.
         *
         * @param {{label: string, value: string}[]} taxClasses
         */
        const populateTaxClasses = (taxClasses) => {
            const taxSelector = templateService.getComponent('pl-tax-class-select');

            while (taxSelector.firstChild) {
                taxSelector.firstChild.remove();
            }

            taxClasses.forEach(taxClass => {
                const option = document.createElement('option');
                option.value = taxClass.value;
                option.innerHTML = taxClass.label;
                taxSelector.appendChild(option);
            });

            taxSelector.value = serviceModel.taxClass || taxClasses[0].value;
        };

        /**
         * Handles a click to a Custom pricing policy enable button.
         *
         * @param {HTMLElement} btn
         */
        const handlePolicySwitchButton = (btn) => {
            const pricingSection = templateService.getComponent('pl-add-price-section'),
                pricingPoliciesSection = templateService.getComponent('pl-pricing-policies'),
                firstServiceDescription = templateService.getComponent('pl-first-service-description'),
                addServiceButton = pricingSection.querySelector('button');

            if (btn.classList.contains('pl-selected')) {
                utilityService.showElement(pricingSection);
                if (serviceModel.pricingPolicies.length > 0) {
                    utilityService.hideElement(firstServiceDescription);
                    addServiceButton.innerHTML = translator.translate('shippingServices.addAnotherPolicy');
                } else {
                    utilityService.showElement(firstServiceDescription);
                    addServiceButton.innerHTML = translator.translate('shippingServices.addFirstPolicy');
                }
            } else {
                utilityService.hideElement(pricingSection);
                utilityService.hideElement(pricingPoliciesSection);
            }

            addServiceButton.addEventListener('click', addNewPolicy);
        };

        /**
         * Sets countries selection labels.
         */
        const setCountrySelection = () => {
            const section = templateService.getComponent('pl-countries-section'),
                button = templateService.getComponent('pl-select-countries'),
                label = templateService.getComponent('pl-selected-countries'),
                selectedCountries = serviceModel.shippingCountries.length;

            utilityService.showElement(section);
            button.innerHTML = translator.translate('shippingServices.openCountries');

            if (selectedCountries === 0 || serviceModel.isShipToAllCountries) {
                label.innerHTML = translator.translate('shippingServices.allCountriesSelected');
            } else if (selectedCountries === 1) {
                label.innerHTML = translator.translate('shippingServices.oneCountrySelected');
            } else {
                label.innerHTML = translator.translate('shippingServices.selectedCountries', [selectedCountries]);
            }
        };

        /**
         * Handles click on Add new policy button.
         *
         * @param {Event} event
         * @return {boolean}
         */
        const addNewPolicy = (event) => {
            event.preventDefault();

            // open modal

            return false;
        };

        /**
         * Saves the service.
         */
        const save = () => {
            const form = templateService.getComponent('pl-edit-service-form');
            if (validationService.validateForm(form)) {
                serviceModel.activated = true;
                serviceModel.showLogo = form['showLogo'].checked;
                serviceModel.name = form['name'].value;
                serviceModel.taxClass = form['tax'].value;

                ajaxService.post(
                    configuration.saveServiceUrl,
                    serviceModel,
                    () => {
                        state.goToState('my-shipping-services');
                    },
                    Packlink.responseService.errorHandler
                );
            }
        };
    }

    Packlink.EditServiceController = EditServiceController;
})();
