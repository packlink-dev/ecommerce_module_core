if (!window.Packlink) {
    window.Packlink = {};
}

(function () {
    /**
     * @typedef EditServiceControllerConfiguration
     * @property {string} getServiceUrl
     * @property {string} saveServiceUrl
     * @property {string} getTaxClassesUrl
     * @property {string} getCountriesListUrl
     * @property {string} getCurrencyDetailsUrl
     * @property {boolean} hasTaxConfiguration
     * @property {boolean} hasCountryConfiguration
     * @property {boolean} canDisplayCarrierLogos
     * @property {int} [maxTitleLength]
     */

    /**
     * @typedef ShippingPricingPolicy
     * @property {int} range_type
     * @property {float} from_weight
     * @property {float} to_weight
     * @property {float} from_price
     * @property {float} to_price
     * @property {int} pricing_policy
     * @property {boolean} increase
     * @property {float} change_percent
     * @property {float} fixed_price
     * @property {string} system_id
     * @property {string} currency
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
        /**
         * @type ShippingService|null
         */
        let originalServiceModel = null;
        let newService = false;
        let fromPick = false;
        let systemInfos;
        let isMultistore = false;
        let getServiceUrl;
        let form;
        let pricePolicyControllers = [];
        let currentSystem;

        /**
         * Shipping method fields that are required in the validation.
         *
         * @type {string[]}
         */
        let modelFields = [
            'name',
            'showLogo',
            'tax',
        ];

        /**
         * Displays page content.
         *
         * @param {{id: string, fromPick: boolean}} config
         */
        this.display = (config) => {
            fromPick = config.fromPick;
            templateService.setCurrentTemplate(templateId);
            form = templateService.getComponent('pl-edit-service-form');
            getServiceUrl = configuration.getServiceUrl;

            if (-1 === getServiceUrl.indexOf('?')) {
                getServiceUrl += '?id=' + config.id;
            } else {
                getServiceUrl += '&id=' + config.id;
            }

            ajaxService.get(getServiceUrl, getService);

            const mainPage = templateService.getMainPage(),
                backButton = mainPage.querySelector('.pl-sub-header button');

            backButton.addEventListener('click', () => {
                goBack(config.fromPick);
            });
        };

        /**
         * Navigates to the previous page.
         *
         * @param {boolean} fromPickServicesPage
         */
        const goBack = (fromPickServicesPage) => {
            const prevState = fromPickServicesPage ? 'pick-shipping-service' : 'my-shipping-services';

            if (JSON.stringify(serviceModel) !== JSON.stringify(originalServiceModel)) {
                const modal = new Packlink.modalService({
                    content: '<div class="pl-text-center">' +
                        '<p class="pl-modal-subtitle pl-separate-vertically">' +
                        translator.translate('shippingServices.discardChangesQuestion') + '</p>' +
                        '</div>',
                    canClose: false,
                    buttons: [
                        {
                            title: translator.translate('general.discard'),
                            onClick: () => {
                                modal.close();
                                state.goToState(prevState);
                            }
                        },
                        {
                            title: translator.translate('general.cancel'),
                            primary: true,
                            onClick: () => {
                                modal.close();
                            }
                        }
                    ]
                });

                modal.open();
            } else {
                state.goToState(prevState);
            }
        };

        /**
         * Fetches current shipping service for editing.
         *
         * @param {ShippingService} service
         */
        const getService = (service) => {
            serviceModel = service;

            ajaxService.get(configuration.getCurrencyDetailsUrl, initSystems);
        };

        /**
         * Retrieves all systems with their information and initializes pricing policies for them.
         *
         * @param {SystemInfo[]} systemInfoResponse
         */
        const initSystems = (systemInfoResponse) => {
            systemInfos = systemInfoResponse;

            if (serviceModel.fixedPrices === null || serviceModel.fixedPrices.length === 0) {
                serviceModel.fixedPrices = {};
            }

            if (serviceModel.systemDefaults === null || serviceModel.systemDefaults.length === 0) {
                serviceModel.systemDefaults = {};
            }

            if (systemInfos.length > 1) {
                let multistoreSelectorGroup = templateService
                    .getComponent('pl-edit-service-form')
                    .querySelector('#pl-multistore-selector-group');

                isMultistore = true;
                systemInfos.unshift({
                    'system_id': 'default',
                    'system_name': 'Default',
                    'currencies': ['EUR']
                });
                multistoreSelectorGroup.classList.remove('pl-hidden');
                currentSystem = 'default';

                document.querySelector('#pl-scope-select').addEventListener('change', function (event) {
                    currentSystem = event.target.value;
                    pricePolicyControllers[currentSystem].display(form);
                    refreshFieldValidation();
                });
            }

            systemInfos.forEach((info) => {
                let storePricePolicyController = new Packlink.SingleStorePricePolicyController();
                storePricePolicyController.init({
                    service: serviceModel,
                    systemInfo: info,
                    isMultistore: isMultistore,
                    onSave: bindService
                });

                if (isMultistore) {
                    let scopeSelector = document.querySelector('#pl-scope-select'),
                        option = document.createElement("option");
                    option.text = info.system_name;
                    option.value = info.system_id;
                    scopeSelector.add(option);
                    if (!serviceModel.systemDefaults.hasOwnProperty(info.system_id)) {
                        serviceModel.systemDefaults[info.system_id] = true;
                    }
                }

                pricePolicyControllers[info.system_id] = storePricePolicyController;
            });

            currentSystem = systemInfos[0].system_id;

            bindService();
        };

        /**
         * Refreshes field validation and adds an additional field.
         */
        const refreshFieldValidation = () => {
            let fieldsForValidation = modelFields.slice(),
                field = pricePolicyControllers[currentSystem].getAdditionalFieldForValidation();

            if (field !== null) {
                fieldsForValidation.push(field);
            }

            validationService.setFormValidation(form, fieldsForValidation);
        };

        /**
         * Binds currenct shipping service.
         */
        const bindService = () => {
            if (!originalServiceModel) {
                originalServiceModel = utilityService.cloneObject(serviceModel);
            }

            newService = !serviceModel.activated;

            refreshFieldValidation();

            form['name'].value = serviceModel.name;
            form['name'].addEventListener('blur', () => {
                serviceModel.name = form['name'].value;
            });

            if (configuration.canDisplayCarrierLogos) {
                utilityService.showElement(templateService.getComponent('pl-show-logo-group'));
                form['showLogo'].checked = serviceModel.showLogo;
                form['showLogo'].addEventListener('change', () => {
                    serviceModel.showLogo = form['showLogo'].checked;
                });
            }

            pricePolicyControllers[currentSystem].display(form);

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

            templateService.clearComponent(taxSelector);

            taxClasses.forEach(taxClass => {
                const option = document.createElement('option');
                option.value = taxClass.value;
                option.innerHTML = taxClass.label;
                taxSelector.appendChild(option);
            });

            taxSelector.value = serviceModel.taxClass || taxClasses[0].value;

            taxSelector.addEventListener('change', () => {
                serviceModel.taxClass = taxSelector.value;
            });
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

            button.addEventListener('click', openCountriesSelectionModal);

            if (selectedCountries === 0 || serviceModel.isShipToAllCountries) {
                label.innerHTML = translator.translate('shippingServices.allCountriesSelected');
            } else if (selectedCountries === 1) {
                label.innerHTML = translator.translate('shippingServices.oneCountrySelected');
            } else {
                label.innerHTML = translator.translate('shippingServices.selectedCountries', [selectedCountries]);
            }
        };

        /**
         * Saves the service.
         */
        const save = () => {
            const form = templateService.getComponent('pl-edit-service-form');
            let excludedElementNames = [];

            if (!configuration.hasTaxConfiguration) {
                excludedElementNames.push('tax');
            }

            for (let systemId in pricePolicyControllers) {
                if (pricePolicyControllers.hasOwnProperty(systemId)) {
                    let fieldName = pricePolicyControllers[systemId].getExcludedFieldForValidation();
                    if (fieldName !== null) {
                        excludedElementNames.push(fieldName);
                    }
                }
            }

            if (validationService.validateForm(form, excludedElementNames) && validateMiconfiguredPolicies()) {
                let pricingPolicies = [],
                    pricingPoliciesEnabled = document.querySelector('.pl-switch .pl-switch-button.pl-selected .pl-switch-on');

                if (pricingPoliciesEnabled) {
                    for (let systemId in pricePolicyControllers) {
                        if (pricePolicyControllers.hasOwnProperty(systemId)) {
                            pricingPolicies = pricingPolicies.concat(pricePolicyControllers[systemId].getSystemPricingPolicies());
                        }
                    }
                }

                serviceModel.activated = true;
                serviceModel.pricingPolicies = pricingPolicies;

                Packlink.utilityService.showSpinner();
                ajaxService.post(
                    configuration.saveServiceUrl,
                    serviceModel,
                    () => {
                        if (fromPick) {
                            state.goToState('pick-shipping-service', {from: 'edit', newService: newService});
                        } else {
                            state.goToState('my-shipping-services');
                        }
                    },
                    Packlink.responseService.errorHandler
                );
            }
        };

        /**
         * Validates that there are no misconfigured pricing policies.
         *
         * @returns {boolean}
         */
        const validateMiconfiguredPolicies = () => {
            let misconfiguredPolicies = form.querySelectorAll('.pl-invalid-policy');

            return misconfiguredPolicies.length === 0;
        }

        /**
         * Opens countries selection modal.
         *
         * @param {Event} event
         * @returns {boolean}
         */
        const openCountriesSelectionModal = (event) => {
            event.preventDefault();
            const ctrl = new Packlink.ServiceCountriesModalController({getCountriesListUrl: configuration.getCountriesListUrl});
            // noinspection JSCheckFunctionSignatures
            ctrl.display({
                service: serviceModel,
                onSave: bindService
            });

            return false;
        };
    }

    Packlink.EditServiceController = EditServiceController;
})();
