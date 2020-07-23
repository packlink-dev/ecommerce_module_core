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

        const rangeTypes = {
            'price' : 0,
            'weight': 1,
            'weightAndPrice': 2
        };

        const pricingPolicies = {
            'packlink' : 0,
            'percent': 1,
            'fixed': 2
        };

        /**
         * Displays page content.
         *
         * @param {{id: string, fromPick: boolean}} config
         */
        this.display = function (config) {
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

            let editBtns = pricingPolicies.getElementsByClassName('pl-edit-pricing-policy');
            for (let i = 0; i < editBtns.length; i++) {
                editBtns[i].addEventListener('click', (event) => {
                    initializePricingPolicyModal(event, i);
                });
            }

            let clearBtns = pricingPolicies.getElementsByClassName('pl-clear-pricing-policy');
            for (let i = 0; i < clearBtns.length; i++) {
                clearBtns[i].addEventListener('click', (event) => {
                    deletePricingPolicy(event, i);
                });
            }

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
            let currentPolicy = policyIndex !== null ? serviceModel.pricingPolicies[policyIndex] : null;

            let modal = new Packlink.modalService({
                content: templateService.getTemplate('pl-pricing-policy-modal'),
                canClose: false,
                buttons: [
                    {
                        title: translator.translate('general.save'),
                        cssClasses: ['pl-button-primary'],
                        onClick: () =>  {
                            savePricingPolicy(currentPolicy, policyIndex, modal)
                        }
                    },
                    {
                        title: translator.translate('general.cancel'),
                        cssClasses: ['pl-button-secondary'],
                        onClick: () => {
                            modal.close();
                        }
                    }
                ],
                onOpen: () => {
                    setPricingPolicyInitialState(currentPolicy, policyIndex);
                }
            });

            modal.open();

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
         * Saves pricing policy
         * @param {ShippingPricingPolicy | null} currentPolicy
         * @param {number} policyIndex
         * @param {ModalService} modal
         */
        const savePricingPolicy = (currentPolicy, policyIndex, modal) => {
            const form = templateService.getComponent('pl-pricing-policy-form');
            if (!validatePricingPolicyForm(form)) {
                return;
            }

            let pricingPolicy = {};
            pricingPolicyModelFields.forEach(function (field) {
                pricingPolicy[field] = form[field].value !== '' ? form[field].value : null;
            });

            pricingPolicy.increase = form['increase'].checked;
            removeUnneededFieldsFromModel(pricingPolicy);

            if (currentPolicy === null) {
                serviceModel.pricingPolicies.push(pricingPolicy);
            } else {
                serviceModel.pricingPolicies[policyIndex] = pricingPolicy;
            }

            bindService(serviceModel);
            modal.close();
        };

        /**
         * Removes not needed fields from model.
         *
         * @param {ShippingPricingPolicy} pricingPolicy
         */
        const removeUnneededFieldsFromModel = (pricingPolicy) => {
            if (parseInt(pricingPolicy.range_type) === rangeTypes.price) {
                pricingPolicy.from_weight = null;
                pricingPolicy.to_weight = null;
            } else if (parseInt(pricingPolicy.range_type) === rangeTypes.weight) {
                pricingPolicy.from_price = null;
                pricingPolicy.to_price = null;
            }

            if (parseInt(pricingPolicy.pricing_policy) !== pricingPolicies.percent) {
                pricingPolicy.change_percent = null;

            } else if (parseInt(pricingPolicy.pricing_policy) !== pricingPolicies.fixed) {
                pricingPolicy.fixed_price = null;
            }
        }

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
         * Sets pricing policy form initial state.
         *
         * @param {ShippingPricingPolicy | null} pricingPolicy
         * @param {number} index
         */
        const setPricingPolicyInitialState = (pricingPolicy, index) => {
            let priceRangeSelect = templateService.getComponent('pl-range-type-select');
            let pricingPolicySelect = templateService.getComponent('pl-pricing-policy-select');
            let pricingPolicyForm = templateService.getComponent('pl-pricing-policy-form');
            let currentIndex = serviceModel.pricingPolicies.length + 1;

            if (pricingPolicy !== null) {
                currentIndex = index + 1;
                pricingPolicyForm['range_type'].value = pricingPolicy.range_type;
                pricingPolicyForm['from_weight'].value = pricingPolicy.from_weight;
                pricingPolicyForm['to_weight'].value = pricingPolicy.to_weight;
                pricingPolicyForm['from_price'].value = pricingPolicy.from_price;
                pricingPolicyForm['to_price'].value = pricingPolicy.to_price;
                pricingPolicyForm['pricing_policy'].value = pricingPolicy.pricing_policy;
                pricingPolicyForm['increase'].checked = pricingPolicy.increase;
                pricingPolicyForm['change_percent'].value = pricingPolicy.change_percent;
                pricingPolicyForm['fixed_price'].value = pricingPolicy.fixed_price;
            }

            templateService.getComponent('pl-pricing-policy-title').innerHTML =
                translator.translate('shippingServices.singlePricePolicy', [currentIndex]);

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
        };

        /**
         * Sets form validation.
         *
         * @param {HTMLElement} form
         * @param {string[]} fields
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
        };

        /**
         * Sets price range section.
         */
        const setPriceRangeSection = () => {
            let priceRangeSelect = templateService.getComponent('pl-range-type-select');
            let fromToPriceWrapper = templateService.getComponent('pl-from-to-price-wrapper');
            let fromToWeightWrapper = templateService.getComponent('pl-from-to-weight-wrapper');

            utilityService.showElement(fromToPriceWrapper);
            utilityService.showElement(fromToWeightWrapper);

            if (parseInt(priceRangeSelect.value) === rangeTypes.price) {
                utilityService.hideElement(fromToWeightWrapper);
            } else if (parseInt(priceRangeSelect.value) === rangeTypes.weight) {
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

            if (parseInt(pricingPolicySelect.value) === pricingPolicies.percent) {
                utilityService.showElement(pricePercentageWrapper);
            } else if (parseInt(pricingPolicySelect.value) === pricingPolicies.fixed) {
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
            } else {
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
            const rangeType = parseInt(pricingPolicy['range_type'].value),
                currentPricingPolicy = parseInt(pricingPolicy['pricing_policy'].value);

            if (!validateRange(rangeType, pricingPolicy['from_price'], pricingPolicy['to_price'], rangeTypes.price) ||
                !validateRange(rangeType, pricingPolicy['from_weight'], pricingPolicy['to_weight'], rangeTypes.weight)
            ) {
                return false;
            }

            if (currentPricingPolicy === pricingPolicies.percent &&
                (!validationService.validateRequiredField(pricingPolicy['change_percent']) ||
                    !validationService.validateNumber(pricingPolicy['change_percent']))
            ) {
                return false;
            }

            if (currentPricingPolicy === pricingPolicies.fixed &&
                (!validationService.validateRequiredField(pricingPolicy['fixed_price']) ||
                    !validationService.validateNumber(pricingPolicy['fixed_price']))
            ) {
                return false;
            }

            return true;
        };

        /**
         * Validates the given range.
         *
         * @param {int} currentRangeType
         * @param {HTMLInputElement} fromRange
         * @param {HTMLInputElement} toRange
         * @param {int} rangeTypeCondition
         * @returns {boolean}
         */
        const validateRange = (currentRangeType, fromRange, toRange, rangeTypeCondition) => {
            if (currentRangeType === rangeTypeCondition || currentRangeType === rangeTypes.weightAndPrice) {
                if (!validationService.validateRequiredField(fromRange) ||
                    !validationService.validateNumber(fromRange) ||
                    !validationService.validateNumber(toRange)
                ) {
                    return false;
                }

                if (parseFloat(fromRange.value) >= parseFloat(toRange.value)) {
                    validationService.setError(
                        toRange,
                        translator.translate('shippingServices.invalidRange')
                    );
                    return false;
                }
            }

            return true;
        };

        /**
         * Opens countries selection modal.
         *
         * @param {Event} event
         * @returns {boolean}
         */
        const openCountriesSelectionModal = (event) => {
            event.preventDefault();
            let modal = new Packlink.modalService({
                content: templateService.getTemplate('pl-countries-selection-modal'),
                canClose: false,
                fullWidthBody: true,
                title: translator.translate('shippingServices.selectCountriesHeader'),
                buttons: [
                    {
                        title: translator.translate('general.accept'),
                        cssClasses: ['pl-button-primary'],
                        onClick: () => {
                            saveCountriesSelection(modal);
                        }
                    },
                    {
                        title: translator.translate('general.cancel'),
                        cssClasses: ['pl-button-secondary'],
                        onClick: () => {
                            modal.close();
                        }
                    }
                ],
                onOpen: () => {
                    ajaxService.get(configuration.getCountriesListUrl, setCountriesSelectionInitialState);
                }
            });

            modal.open();

            return false;
        };

        /**
         * Saves countries selection.
         *
         * @param {ModalService} modal
         */
        const saveCountriesSelection = (modal) => {
            const countriesSelectionForm = templateService.getComponent('pl-countries-selection-form'),
                allCountries = countriesSelectionForm.querySelectorAll('.pl-shipping-country-selection-wrapper input'),
                selectedCountries = countriesSelectionForm.querySelectorAll('.pl-shipping-country-selection-wrapper input:checked');
            serviceModel.shippingCountries = [];
            serviceModel.isShipToAllCountries = allCountries.length === selectedCountries.length;

            if (!serviceModel.isShipToAllCountries) {
                selectedCountries.forEach(
                    (input) => {
                        serviceModel.shippingCountries.push(input.name);
                    }
                );
            }

            bindService(serviceModel);
            modal.close();
        };

        const renderPricingPolicies = () => {
            const pricingPolicies = templateService.getComponent('pl-pricing-policies')
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
            const toWeight = policy.to_weight !== null ? policy.to_weight : '-';
            const toPrice = policy.to_price !== null ? policy.to_price : '-';

            let rangeType = translator.translate('shippingServices.priceRangeWithData', [policy.from_price, toPrice]);

            if (parseInt(policy.range_type) === rangeTypes.weight) {
                rangeType = translator.translate('shippingServices.weightRangeWithData', [policy.from_weight, toWeight]);
            } else if (parseInt(policy.range_type) === rangeTypes.weightAndPrice) {
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
            if (parseInt(policy.pricing_policy) === pricingPolicies.percent) {
                result = translator.translate('' +
                    'shippingServices.percentagePacklinkPricesWithData',
                    [translator.translate('shippingServices.' + (policy.increase ? 'increase' : 'reduce')), policy.change_percent]
                );
            } else if (parseInt(policy.pricing_policy) === pricingPolicies.fixed) {
                result = translator.translate('shippingServices.fixedPricesWithData', [policy.fixed_price]);
            }

            return result;
        };

        /**
         * Sets countries selection initial state.
         *
         * @param {[{value: string, label: string}]} listOfCountries
         */
        const setCountriesSelectionInitialState = (listOfCountries) => {
            const shippingCountryWrapper = templateService.getComponent('pl-shipping-country-selection-wrapper');
            shippingCountryWrapper.innerHTML = '';
            listOfCountries.forEach((country) => {
                shippingCountryWrapper.innerHTML += '<div class="pl-checkbox pl-country-checkbox-wrapper pl-no-margin">' +
                    '<input type="checkbox" name="' + country.value + '" id="pl-' + country.value + '">' +
                    '<label for="pl-' + country.value + '">' +
                    country.label +
                    '</label>' +
                    '</div>';
            });

            const countriesSelectionForm = templateService.getComponent('pl-countries-selection-form'),
                countryInputs = countriesSelectionForm.querySelectorAll('.pl-shipping-country-selection-wrapper input');

            countriesSelectionForm['isShipToAllCountries'].checked = serviceModel.isShipToAllCountries;

            if (serviceModel.isShipToAllCountries) {
                countryInputs.forEach((input) => {
                    input.checked = true;
                });
            } else {
                serviceModel.shippingCountries.forEach((country) => {
                    countriesSelectionForm[country].checked = true;
                });

                handleCountrySelectionChanged();
            }

            setCountryChangeEvents(countryInputs);
            setShipToAllCountriesChangeEvent(countriesSelectionForm);
        };

        /**
         * Handles country selection changed.
         */
        const handleCountrySelectionChanged = () => {
            const countriesSelectionForm = templateService.getComponent('pl-countries-selection-form');
            const selectedCountries = countriesSelectionForm.querySelectorAll('.pl-shipping-country-selection-wrapper input:checked');
            const label = templateService.getComponent('pl-check-all-countries');
            const countryInputs = countriesSelectionForm.querySelectorAll('.pl-shipping-country-selection-wrapper input');
            countriesSelectionForm['isShipToAllCountries'].checked = selectedCountries.length > 0;

            if (selectedCountries.length === countryInputs.length || selectedCountries.length === 0) {
                label.innerHTML = translator.translate('shippingServices.selectAllCountries');
            } else if (selectedCountries.length === 1) {
                label.innerHTML = translator.translate('shippingServices.oneCountrySelected');
            } else {
                label.innerHTML = translator.translate('shippingServices.selectedCountries', [selectedCountries.length]);
            }
        };

        /**
         * Sets event listeners for country change.
         *
         * @param {[HTMLInputElement]}countryInputs
         */
        const setCountryChangeEvents = (countryInputs) => {
            countryInputs.forEach((input) => {
                markSelectedCountry(input);

                input.addEventListener('change', () => {
                    markSelectedCountry(input);
                    handleCountrySelectionChanged();
                });
            });
        };

        /**
         * Sets event listener for shipToAllCountries checkbox.
         *
         * @param {HTMLFormElement} countriesSelectionForm
         */
        const setShipToAllCountriesChangeEvent = (countriesSelectionForm) => {
            countriesSelectionForm['isShipToAllCountries'].addEventListener('change', (event) => {
                const label = templateService.getComponent('pl-check-all-countries');
                countriesSelectionForm.querySelectorAll('.pl-shipping-country-selection-wrapper input').forEach((input) => {
                    input.checked = event.target.checked;
                    if (!input.checked) {
                        label.innerHTML = translator.translate('shippingServices.selectAllCountries');
                    }
                    markSelectedCountry(input);
                });
            });
        };

        /**
         * Marks selected country.
         *
         * @param {HTMLInputElement} input
         */
        const markSelectedCountry = (input) => {
            let inputWrapper = input.parentElement;

            inputWrapper.classList.remove('pl-shipping-country-selected');
            if (input.checked) {
                inputWrapper.classList.add('pl-shipping-country-selected');
            }
        };
    }

    Packlink.EditServiceController = EditServiceController;
})();
