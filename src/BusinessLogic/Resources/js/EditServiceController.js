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
        let newService = false;

        const modelFields = [
            'name',
            'showLogo',
            'tax',
        ];

        const rangeTypes = {
            'price': '0',
            'weight': '1',
            'weightAndPrice': '2'
        };

        const pricingPolicies = {
            'packlink': '0',
            'percent': '1',
            'fixed': '2'
        };

        /**
         * Displays page content.
         *
         * @param {{id: string, fromPick: boolean}} config
         */
        this.display = (config) => {
            templateService.setCurrentTemplate(templateId);
            ajaxService.get(configuration.getServiceUrl + '&id=' + config.id, bindService);

            const mainPage = templateService.getMainPage(),
                backButton = mainPage.querySelector('.pl-sub-header button'),
                policySwitchButton = templateService.getComponent('pl-configure-prices-button'),
                addServiceButton = document.querySelector('#pl-add-price-section button');

            backButton.addEventListener('click', () => {
                state.goToState(config.fromPick ? 'pick-shipping-service' : 'my-shipping-services');
            });

            policySwitchButton.addEventListener('click', () => {
                policySwitchButton.classList.toggle('pl-selected');
                handlePolicySwitchButton(policySwitchButton);
            });

            addServiceButton.addEventListener('click', initializePricingPolicyModal);
        };

        /**
         * Binds service.
         *
         * @param {ShippingService} service
         */
        const bindService = (service) => {
            const form = templateService.getComponent('pl-edit-service-form');
            serviceModel = service;
            newService = !service.activated;

            validationService.setFormValidation(form, modelFields);

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

            if (serviceModel.pricingPolicies.length > 0) {
                setPricingPolicies();
            } else {
                const policySwitchButton = templateService.getComponent('pl-configure-prices-button');
                handlePolicySwitchButton(policySwitchButton);
            }

            templateService.getComponent('pl-use-packlink-price-if-not-in-range').checked = serviceModel.usePacklinkPriceIfNotInRange;

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
                    setPricingPolicies();
                } else {
                    utilityService.showElement(firstServiceDescription);
                    utilityService.hideElement(pricingPoliciesSection);
                    addServiceButton.innerHTML = translator.translate('shippingServices.addFirstPolicy');
                }
            } else {
                utilityService.hideElement(templateService.getComponent('pl-use-packlink-price-wrapper'));
                utilityService.hideElement(pricingSection);
                utilityService.hideElement(pricingPoliciesSection);
            }
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
         * Sets pricing policies section.
         */
        const setPricingPolicies = () => {
            const pricingPolicies = templateService.getComponent('pl-pricing-policies'),
                addServiceButton = document.querySelector('#pl-add-price-section button'),
                policySwitchButton = templateService.getComponent('pl-configure-prices-button'),
                pricingSection = templateService.getComponent('pl-add-price-section');

            utilityService.showElement(pricingSection);
            policySwitchButton.classList.add('pl-selected');

            utilityService.hideElement(templateService.getComponent('pl-first-service-description'));
            utilityService.showElement(pricingPolicies);
            addServiceButton.innerHTML = translator.translate('shippingServices.addAnotherPolicy');

            renderPricingPolicies();

            let editButtons = pricingPolicies.getElementsByClassName('pl-edit-pricing-policy');
            utilityService.toArray(editButtons).forEach((button, index) => {
                button.addEventListener('click', (event) => {
                    initializePricingPolicyModal(event, index);
                });
            });

            let clearButtons = pricingPolicies.getElementsByClassName('pl-clear-pricing-policy');
            utilityService.toArray(clearButtons).forEach((button, index) => {
                button.addEventListener('click', (event) => {
                    deletePricingPolicy(event, index);
                });
            });

            utilityService.showElement(templateService.getComponent('pl-use-packlink-price-wrapper'));
        };

        /**
         * Sets initial state to pricing policy form in modal.
         *
         * @param {Event} event
         * @param {int | null} policyIndex
         *
         * @returns {boolean}
         */
        const initializePricingPolicyModal = (event, policyIndex = null) => {
            event.preventDefault();

            const ctrl = new Packlink.PricePolicyController();
            // noinspection JSCheckFunctionSignatures
            ctrl.display({
                service: serviceModel,
                policyIndex: policyIndex,
                onSave: bindService
            });

            return false;
        };

        /**
         * Deletes pricing policy from memory.
         *
         * @param {Event} event
         * @param {number} i
         * @returns {boolean}
         */
        const deletePricingPolicy = (event, i) => {
            event.preventDefault();
            serviceModel.pricingPolicies.splice(i, 1);
            bindService(serviceModel);
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
                serviceModel.usePacklinkPriceIfNotInRange = form['usePacklinkPriceIfNotInRange'].checked;

                ajaxService.post(
                    configuration.saveServiceUrl,
                    serviceModel,
                    () => {
                        state.goToState('my-shipping-services', {from: 'edit', newService: newService});
                    },
                    Packlink.responseService.errorHandler
                );
            }
        };

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

        const renderPricingPolicies = () => {
            const pricingPolicies = templateService.getComponent('pl-pricing-policies');
            const parent = pricingPolicies.querySelector('.pl-pricing-policies');
            parent.innerHTML = '';

            serviceModel.pricingPolicies.forEach((policy, index) => {
                const template = templateService.getComponent('pl-pricing-policy-list-item'),
                    itemEl = document.createElement('div');

                itemEl.innerHTML = template.innerHTML;

                parent.appendChild(itemEl);

                itemEl.querySelector('#pl-price-range-title').innerHTML =
                    translator.translate('shippingServices.singlePricePolicy', [index + 1]);

                itemEl.querySelector('#pl-price-range-wrapper span').innerHTML =
                    getPolicyRangeTypeLabel(policy);

                itemEl.querySelector('#pl-price-policy-range-wrapper span').innerHTML =
                    getPricingPolicyLabel(policy);
            });
        };

        /**
         * Gets range type label.
         * @param {ShippingPricingPolicy} policy
         * @returns {string}
         */
        const getPolicyRangeTypeLabel = (policy) => {
            const toWeight = policy.to_weight || '-';
            const toPrice = policy.to_price || '-';

            let rangeType = translator.translate('shippingServices.priceRangeWithData', [policy.from_price, toPrice]);

            if (policy.range_type.toString() === rangeTypes.weight) {
                rangeType = translator.translate('shippingServices.weightRangeWithData', [policy.from_weight, toWeight]);
            } else if (policy.range_type.toString() === rangeTypes.weightAndPrice) {
                rangeType = translator.translate(
                    'shippingServices.weightAndPriceRangeWithData',
                    [policy.from_weight, toWeight, policy.from_price, toPrice]
                );
            }

            return rangeType;
        };

        /**
         * Gets range type label.
         * @param {ShippingPricingPolicy} policy
         * @returns {string}
         */
        const getPricingPolicyLabel = (policy) => {
            let result = translator.translate('shippingServices.packlinkPrice');
            if (policy.pricing_policy.toString() === pricingPolicies.percent) {
                result = translator.translate('' +
                    'shippingServices.percentagePacklinkPricesWithData',
                    [translator.translate('shippingServices.' + (policy.increase ? 'increase' : 'reduce')), policy.change_percent]
                );
            } else if (policy.pricing_policy.toString() === pricingPolicies.fixed) {
                result = translator.translate('shippingServices.fixedPricesWithData', [policy.fixed_price]);
            }

            return result;
        };
    }

    Packlink.EditServiceController = EditServiceController;
})();
