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

        const modelFields = [
            'name',
            'showLogo',
            'tax',
        ];

        const pricingPolicyModelFields = [
            'range_type',
            'from_weight',
            'to_weight',
            'from_price',
            'to_price',
            'pricing_policy',
            'increase',
            'change_percent',
            'fixed_price',
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
                policySwitchButton = templateService.getComponent('pl-configure-prices-button'),
                addServiceButton = document.querySelector('#pl-add-price-section button');

            backButton.addEventListener('click', () => {
                state.goToState('my-shipping-services');
            });

            policySwitchButton.addEventListener('click', () => {
                policySwitchButton.classList.toggle('pl-selected');
                handlePolicySwitchButton(policySwitchButton);
            });

            addServiceButton.addEventListener('click', (event) => {
                initializePricingPolicyModal(event);
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

            setFormValidation(form, modelFields);

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
                    setPricingPolicies();
                } else {
                    utilityService.showElement(firstServiceDescription);
                    utilityService.hideElement(pricingPoliciesSection);
                    addServiceButton.innerHTML = translator.translate('shippingServices.addFirstPolicy');
                }
            } else {
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
                addServiceButton = document.querySelector('#pl-add-price-section button');

            utilityService.hideElement(templateService.getComponent('pl-first-service-description'));
            utilityService.showElement(pricingPolicies);
            addServiceButton.innerHTML = translator.translate('shippingServices.addAnotherPolicy');

            pricingPolicies.innerHTML = '';

            serviceModel.pricingPolicies.forEach((policy) => {
                pricingPolicies.innerHTML += '<button class="pl-edit-pricing-policy">Edit</button>' +
                    '<button class="pl-clear-pricing-policy">Clear</button>';
            });

            let editBtns = pricingPolicies.getElementsByClassName('pl-edit-pricing-policy');
            for (let i = 0; i < editBtns.length; i++)  {
                editBtns[i].addEventListener('click', (event) => {
                    initializePricingPolicyModal(event, i);
                });
            }

            let clearBtns = pricingPolicies.getElementsByClassName('pl-clear-pricing-policy');
            for (let i = 0; i < clearBtns.length; i++)  {
                clearBtns[i].addEventListener('click', (event) => {
                    event.preventDefault();
                    serviceModel.pricingPolicies.splice(i, 1);
                    ajaxService.post(
                        configuration.saveServiceUrl,
                        serviceModel,
                        () => {
                            bindService(serviceModel);
                        },
                        Packlink.responseService.errorHandler
                    );
                    return false;
                });
            }
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
            let currentPolicy = policyIndex !== null ? serviceModel.pricingPolicies[policyIndex] : null;

            let modal = new Packlink.modalService({
                content: templateService.getTemplate('pl-pricing-policy-modal'),
                canClose: false,
                buttons: [
                    {
                        title: translator.translate('shippingServices.save'),
                        cssClasses: ['pl-button-primary'],
                        onClick: () => {
                            const form = templateService.getComponent('pl-pricing-policy-form');
                            if (!validatePricingPolicyForm(form)) {
                                return;
                            }

                            const pricingPolicy = {
                                'range_type': form['range_type'].value,
                                'from_weight': (form['range_type'].value === '1' || form['range_type'].value === '2') ?
                                    form['from_weight'].value : null,
                                'to_weight': (form['range_type'].value === '1' || form['range_type'].value === '2') ?
                                    form['to_weight'].value : null,
                                'from_price': (form['range_type'].value === '0' || form['range_type'].value === '2') ?
                                    form['from_price'].value : null,
                                'to_price': (form['range_type'].value === '0' || form['range_type'].value === '2') ?
                                    form['to_price'].value : null,
                                'pricing_policy': form['pricing_policy'].value,
                                'increase': form['increase'].checked,
                                'change_percent': form['pricing_policy'].value === '1' ? form['change_percent'].value : null,
                                'fixed_price': form['pricing_policy'].value === '2' ? form['fixed_price'].value : null,
                            };

                            if (currentPolicy === null) {
                                serviceModel.pricingPolicies.push(pricingPolicy);
                            }
                            else {
                                serviceModel.pricingPolicies[policyIndex] = pricingPolicy;
                            }

                            ajaxService.post(
                                configuration.saveServiceUrl,
                                serviceModel,
                                () => {
                                    bindService(serviceModel);
                                    modal.close();
                                },
                                Packlink.responseService.errorHandler
                            );

                        }
                    },
                    {
                        title: translator.translate('shippingServices.cancel'),
                        cssClasses: ['pl-button-secondary'],
                        onClick: () => {
                            modal.close();
                        }
                    }
                ],
                onOpen: () => {
                    setPricingPolicyInitialState(currentPolicy);
                }
            });

            modal.open();

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

        /**
         * Sets pricing policy form initial state.
         *
         * @param {ShippingPricingPolicy | null} pricingPolicy
         */
        const setPricingPolicyInitialState = (pricingPolicy) => {
            let priceRangeSelect = templateService.getComponent('pl-range-type-select');
            let pricingPolicySelect = templateService.getComponent('pl-pricing-policy-select');

            if (pricingPolicy !== null) {
                priceRangeSelect.value = pricingPolicy.range_type;
                templateService.getComponent('pl-from-weight').value = pricingPolicy.from_weight;
                templateService.getComponent('pl-to-weight').value = pricingPolicy.to_weight;
                templateService.getComponent('pl-from-price').value = pricingPolicy.from_price;
                templateService.getComponent('pl-to-price').value = pricingPolicy.to_price;
                pricingPolicySelect.value = pricingPolicy.pricing_policy;
                templateService.getComponent('pl-increase').checked = pricingPolicy.increase;
                templateService.getComponent('pl-change-percent').value = pricingPolicy.change_percent;
                templateService.getComponent('pl-fixed-price').value = pricingPolicy.fixed_price;
            }

            setPriceRangeSection();
            setPricingPolicySection();
            setIncreaseToggle();
            priceRangeSelect.addEventListener('change', setPriceRangeSection);
            pricingPolicySelect.addEventListener('change', setPricingPolicySection);
            templateService.getComponent('pl-price-percentage-increase').addEventListener('click', (event) => {
                handleIncreaseToggleChange(event, true);
            });
            templateService.getComponent('pl-price-percentage-decrease').addEventListener('click', (event) => {
                handleIncreaseToggleChange(event, false);
            });

            setFormValidation(templateService.getComponent('pl-pricing-policy-form'), pricingPolicyModelFields);
        }

        /**
         * Sets form validation.
         *
         * @param form
         * @param fields
         */
        const setFormValidation = (form, fields) => {
            for (const field of fields) {
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
        }

        /**
         * Sets price range section.
         */
        const setPriceRangeSection = () => {
            let priceRangeSelect = templateService.getComponent('pl-range-type-select');
            let fromToPriceWrapper = templateService.getComponent('pl-from-to-price-wrapper');
            let fromToWeightWrapper = templateService.getComponent('pl-from-to-weight-wrapper');

            utilityService.showElement(fromToPriceWrapper);
            utilityService.showElement(fromToWeightWrapper);

            if (parseInt(priceRangeSelect.value) === 0) {
                utilityService.hideElement(fromToWeightWrapper);
            }
            else if (parseInt(priceRangeSelect.value) === 1) {
                utilityService.hideElement(fromToPriceWrapper);
            }
        };

        /**
         * Sets pricing policy section.
         */
        const setPricingPolicySection = () => {
            let pricingPolicySelect = templateService.getComponent('pl-pricing-policy-select');
            let pricePercentageWrapper = templateService.getComponent('pl-price-percentage-wrapper');
            let fixedPriceWrapper = templateService.getComponent('pl-price-fixed-wrapper');

            utilityService.hideElement(fixedPriceWrapper);
            utilityService.hideElement(pricePercentageWrapper);

            if (parseInt(pricingPolicySelect.value) === 1) {
                utilityService.showElement(pricePercentageWrapper);
            }
            else if (parseInt(pricingPolicySelect.value) === 2) {
                utilityService.showElement(fixedPriceWrapper);
            }
        };

        /**
         * Sets increase toggle based on selected option.
         */
        const setIncreaseToggle = () => {
            const increaseButton = templateService.getComponent('pl-price-percentage-increase');
            const decreaseButton = templateService.getComponent('pl-price-percentage-decrease');
            const increaseElement = templateService.getComponent('pl-increase');
            increaseButton.classList.remove('pl-button-primary', 'pl-button-secondary');
            decreaseButton.classList.remove('pl-button-primary', 'pl-button-secondary');

            if (increaseElement.checked) {
                increaseButton.classList.add('pl-button-primary');
                decreaseButton.classList.add('pl-button-secondary');
            }
            else {
                increaseButton.classList.add('pl-button-secondary');
                decreaseButton.classList.add('pl-button-primary');
            }
        };

        /**
         * Toggle changed event handler.
         *
         * @param {Event} event
         * @param {boolean} isChecked
         * @returns {boolean}
         */
        const handleIncreaseToggleChange = (event, isChecked) => {
            templateService.getComponent('pl-increase').checked = isChecked;
            setIncreaseToggle();
            event.preventDefault();
            return false;
        };

        /**
         * Validates pricing policy form.
         *
         * @param {HTMLElement} pricingPolicy
         *
         * @returns {boolean}
         */
        const validatePricingPolicyForm = (pricingPolicy) => {
            if (parseInt(pricingPolicy['range_type'].value) === 0 || parseInt(pricingPolicy['range_type'].value) === 2) {
                if (!validationService.validateRequiredField(pricingPolicy['from_price']) ||
                    !validationService.validateNumber(pricingPolicy['from_price']) ||
                    !validationService.validateNumber(pricingPolicy['to_price'])
                ) {
                    return false;
                }

                if (parseFloat(pricingPolicy['from_price'].value) >= parseFloat(pricingPolicy['to_price'].value)) {
                    validationService.setError(
                        pricingPolicy['to_price'],
                        translator.translate('shippingServices.invalidRange')
                    );
                    return false;
                }
            }

            if (parseInt(pricingPolicy['range_type'].value) === 1 || parseInt(pricingPolicy['range_type'].value) === 2) {
                if (!validationService.validateRequiredField(pricingPolicy['from_weight']) ||
                    !validationService.validateNumber(pricingPolicy['from_weight']) ||
                    !validationService.validateRequiredField(pricingPolicy['to_weight']) ||
                    !validationService.validateNumber(pricingPolicy['to_weight'])
                ) {
                    return false;
                }

                if (parseFloat(pricingPolicy['from_weight'].value) >= parseFloat(pricingPolicy['to_weight'].value)) {
                    validationService.setError(
                        pricingPolicy['to_weight'],
                        translator.translate('shippingServices.invalidRange')
                    );
                    return false;
                }
            }

            if (parseInt(pricingPolicy['pricing_policy'].value) === 1) {
                if (!validationService.validateRequiredField(pricingPolicy['change_percent']) ||
                    !validationService.validateNumber(pricingPolicy['change_percent'])
                ) {
                    return false;
                }
            }

            if (parseInt(pricingPolicy['pricing_policy'].value) === 2) {
                if (!validationService.validateRequiredField(pricingPolicy['fixed_price']) ||
                    !validationService.validateNumber(pricingPolicy['fixed_price'])
                ) {
                    return false;
                }
            }

            return true;
        };
    }

    Packlink.EditServiceController = EditServiceController;
})();
