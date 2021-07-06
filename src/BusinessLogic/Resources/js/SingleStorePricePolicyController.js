if (!window.Packlink) {
    window.Packlink = {};
}

(function () {
    /**
     * @constructor
     */
    function SingleStorePricePolicyController() {
        const templateService = Packlink.templateService,
            utilityService = Packlink.utilityService,
            translator = Packlink.translationService,
            validationService = Packlink.validationService;

        /**
         * Price policy range types.
         *
         * @type {{
         *  weightAndPrice: string,
         *  price: string,
         *  weight: string
         * }}
         */
        const rangeTypes = {
            'price': '0',
            'weight': '1',
            'weightAndPrice': '2'
        };
        /**
         * Pricing policy types.
         *
         * @type {{
         *  packlink: string,
         *  fixed: string,
         *  percent: string
         * }}
         */
        const pricingPolicyTypes = {
            'packlink': '0',
            'percent': '1',
            'fixed': '2'
        };

        /**
         * @type ShippingService
         */
        let serviceModel = {};
        /**
         * @type SystemInfo
         */
        let systemInfo = {};
        let misconfigurationDetected = false;
        let isMultistore = false;
        let onSave;

        /**
         * Displays page content.
         *
         * @param {{
         *  service: ShippingService,
         *  systemInfo: SystemInfo,
         *  isMultistore: boolean,
         *  onSave: function(ShippingService)
         * }} config
         */
        this.init = function (config) {
            serviceModel = config.service;
            systemInfo = config.systemInfo;
            isMultistore = config.isMultistore;
            onSave = config.onSave;

            if (!systemInfo.currencies.includes(serviceModel.currency)) {
                misconfigurationDetected = true;
            }
        };

        /**
         * Displays page content.
         */
        this.display = (form) => {
            resetPricesSection(form);

            if (misconfigurationDetected || systemInfo.system_id === 'default') {
                setDefaultFixedPriceInput(form);
            }

            if (systemInfo.system_id === 'default') {
                renderDefaultScopeConfiguration(form);
            } else {
                renderSingleStoreConfiguration(form);
            }
        };

        /**
         * Returns system-specific additional fields for validation, in case misconfiguration is detected.
         *
         * @returns {string|null}
         */
        this.getAdditionalFieldForValidation = () => {
            if (misconfigurationDetected) {
                if (!isMultistore) {
                    return 'misconfigurationFixedPrice';
                }

                if (!serviceModel.systemDefaults[systemInfo.system_id]) {
                    return 'misconfigurationFixedPrice' + (isMultistore ? systemInfo.system_id : '');
                }
            }

            return null;
        };

        /**
         * Returns system-specific fields that should be excluded in the validation,
         * in case a system is using the default fixed price.
         *
         * @returns {string|null}
         */
        this.getExcludedFieldForValidation = () => {
            if (systemInfo.system_id !== 'default'
                && (!misconfigurationDetected || serviceModel.systemDefaults[systemInfo.system_id])
            ) {
                return 'misconfigurationFixedPrice' + (isMultistore ? systemInfo.system_id : '');
            }

            return null;
        };

        /**
         * Sets system pricing policies.
         *
         * @param {ShippingPricingPolicy[]} policies
         */
        this.setSystemPricingPolicies = (policies) => {
            if (!isMultistore) {
                serviceModel.pricingPolicies = policies;
            } else {
                removeSystemPricingPolicies();
                serviceModel.pricingPolicies = serviceModel.pricingPolicies.concat(policies);
            }
        }

        /**
         * Returns system pricing policies.
         *
         * @returns {ShippingPricingPolicy[]}
         */
        this.getSystemPricingPolicies = () => {
            if (!isMultistore) {
                return serviceModel.pricingPolicies;
            }

            let pricingPolicies = [];
            serviceModel.pricingPolicies.forEach((pricingPolicy) => {
                if (pricingPolicy.system_id === systemInfo.system_id) {
                    pricingPolicies.push(pricingPolicy);
                }
            });

            return pricingPolicies;
        };

        /**
         * Renders default scope configuration.
         *
         * @param {HTMLElement} form
         */
        const renderDefaultScopeConfiguration = (form) => {
            let pricesSection = form.querySelector('#pl-concrete-prices-section'),
                misconfigurationContainer = pricesSection.querySelector('#pl-misconfiguration'),
                misconfigurationMessage = misconfigurationContainer.querySelector('.pl-section-subtitle'),
                useDefaultSection = form.querySelector('#pl-use-default-group'),
                switchSection = pricesSection.querySelector('.pl-switch'),
                addPriceSection = document.querySelector('#pl-add-price-section');

            misconfigurationMessage.classList.add('pl-hidden');
            switchSection.classList.add('pl-hidden');
            addPriceSection.classList.add('pl-hidden');
            useDefaultSection.classList.add('pl-hidden');
        };

        /**
         * Renders single store configuration.
         *
         * @param {HTMLElement} form
         */
        const renderSingleStoreConfiguration = (form) => {
            let pricesSection = form.querySelector('#pl-concrete-prices-section'),
                policySwitchButton = pricesSection.querySelector('#pl-configure-prices-button'),
                addPriceButton = document.querySelector('#pl-add-price-section button'),
                pricingPolicies = isMultistore ? this.getSystemPricingPolicies() : serviceModel.pricingPolicies;

            if (isMultistore) {
                renderMultistoreConfiguration(form);
            }

            policySwitchButton.addEventListener('click', () => {
                policySwitchButton.classList.toggle('pl-selected');
                handlePolicySwitchButton(policySwitchButton);
            });

            if (pricingPolicies.length > 0) {
                policySwitchButton.classList.add('pl-selected');
                handlePolicySwitchButton(policySwitchButton);
            }

            addPriceButton.addEventListener('click', initializePricingPolicyModal);
            initPricingPolicies(form);
        }

        /**
         * Sets default fixed price input when misconfiguration is detected.
         *
         * @param {HTMLElement} form
         */
        const setDefaultFixedPriceInput = (form) => {
            let pricesSection = form.querySelector('#pl-concrete-prices-section'),
                fixedPriceInput = pricesSection.querySelector('#pl-misconfiguration-fixed-price'),
                fixedPriceLabel = fixedPriceInput.parentElement.querySelector('label'),
                scopeSelector = document.querySelector('#pl-scope-select'),
                misconfigurationContainer = pricesSection.querySelector('#pl-misconfiguration');

            misconfigurationContainer.classList.remove('pl-hidden');
            fixedPriceInput.dataset.required = 'true';
            fixedPriceLabel.innerText += (systemInfo.system_id !== 'default' ? ' (' + systemInfo.symbols[systemInfo.currencies[0]] + ')' : '');
            fixedPriceInput.name = 'misconfigurationFixedPrice' + systemInfo.system_id;
            fixedPriceInput.value = getFixedPrice();
            scopeSelector.disabled = isScopeDisabled(fixedPriceInput);
            fixedPriceInput.addEventListener('input', () => {
                setFixedPrice(fixedPriceInput.value);
                validationService.validateInputField(fixedPriceInput);
                scopeSelector.disabled = isScopeDisabled(fixedPriceInput);
            });

            if (serviceModel.activated) {
                validationService.validateInputField(fixedPriceInput);
            } else {
                validationService.removeError(fixedPriceInput);
            }
        };

        /**
         * Renders multi-store configuration.
         *
         * @param {HTMLElement} form
         */
        const renderMultistoreConfiguration = (form) => {
            let pricesSection = form.querySelector('#pl-concrete-prices-section'),
                fixedPriceInput = pricesSection.querySelector('#pl-misconfiguration-fixed-price'),
                scopeSelector = document.querySelector('#pl-scope-select'),
                useDefaultSection = form.querySelector('#pl-use-default-group');

            if (!misconfigurationDetected) {
                useDefaultSection.classList.add('pl-hidden');
            } else {
                let useDefaultCheckbox = useDefaultSection.querySelector('#pl-use-default');
                useDefaultCheckbox.checked = serviceModel.systemDefaults[systemInfo.system_id];
                fixedPriceInput.dataset.required = useDefaultCheckbox.checked ? 'false' : 'true';
                fixedPriceInput.disabled = useDefaultCheckbox.checked;
                if (useDefaultCheckbox.checked) {
                    validationService.removeError(fixedPriceInput);
                }

                useDefaultCheckbox.addEventListener('change', () => {
                    serviceModel.systemDefaults[systemInfo.system_id] = useDefaultCheckbox.checked;
                    fixedPriceInput.dataset.required = useDefaultCheckbox.checked ? 'false' : 'true';
                    fixedPriceInput.disabled = useDefaultCheckbox.checked;
                    scopeSelector.disabled = isScopeDisabled(fixedPriceInput);
                    if (useDefaultCheckbox.checked) {
                        validationService.removeError(fixedPriceInput);
                    } else {
                        validationService.validateInputField(fixedPriceInput);
                    }
                });
            }
        };

        /**
         * Returns whether the scope selector should be disabled.
         *
         * @param {HTMLElement} fixedPriceInput
         *
         * @returns {boolean}
         */
        const isScopeDisabled = (fixedPriceInput) => {
            let result = isMultistore
                && !validationService.validateInputField(fixedPriceInput);

            if (systemInfo.system_id !== 'default') {
                return result && !serviceModel.systemDefaults[systemInfo.system_id];
            }

            return result;
        };

        /**
         * Resets all elements to their initial state when switching the scope in a multi-store environment.
         *
         * @param {HTMLElement} form
         */
        const resetPricesSection = (form) => {
            let previousPricesSection = document.getElementById('pl-concrete-prices-section'),
                pricesSection = form.querySelector('#pl-prices-section'),
                concretePricesSection = pricesSection.cloneNode(true),
                misconfigurationContainer = concretePricesSection.querySelector('#pl-misconfiguration'),
                misconfigurationMessage = misconfigurationContainer.querySelector('.pl-section-subtitle'),
                policySwitchButton = pricesSection.querySelector('#pl-configure-prices-button'),
                useDefaultSection = form.querySelector('#pl-use-default-group'),
                addPriceSection = document.querySelector('#pl-add-price-section'),
                addPriceButton = document.querySelector('#pl-add-price-section button');

            if (previousPricesSection !== null) {
                previousPricesSection.parentElement.removeChild(previousPricesSection);
            }

            useDefaultSection.classList.remove('pl-hidden');
            useDefaultSection.replaceWith(useDefaultSection.cloneNode(true));
            addPriceButton.replaceWith(addPriceButton.cloneNode(true));
            misconfigurationMessage.classList.remove('pl-hidden');
            concretePricesSection.classList.remove('pl-hidden');
            concretePricesSection.id = 'pl-concrete-prices-section';
            pricesSection.parentElement.insertBefore(concretePricesSection, pricesSection.nextElementSibling);
            policySwitchButton.classList.remove('pl-selected');
            addPriceSection.classList.remove('pl-hidden');
        };

        /**
         * Initializes pricing policies.
         *
         * @param {HTMLElement} form
         */
        const initPricingPolicies = (form) => {
            let pricingPolicies = this.getSystemPricingPolicies();

            if (pricingPolicies.length > 0) {
                setPricingPolicies();
            } else {
                const policySwitchButton = templateService.getComponent('pl-configure-prices-button');
                handlePolicySwitchButton(policySwitchButton);
            }

            if (form['usePacklinkPriceIfNotInRange']) {
                form['usePacklinkPriceIfNotInRange'].checked = serviceModel.usePacklinkPriceIfNotInRange;
                form['usePacklinkPriceIfNotInRange'].addEventListener('change', () => {
                    serviceModel.usePacklinkPriceIfNotInRange = form['usePacklinkPriceIfNotInRange'].checked;
                });
            }
        };

        /**
         * Removes system pricing policies.
         */
        const removeSystemPricingPolicies = () => {
            for (let i = serviceModel.pricingPolicies.length - 1; i >= 0; i--) {
                if (serviceModel.pricingPolicies[i].system_id === systemInfo.system_id) {
                    serviceModel.pricingPolicies.splice(i, 1);
                }
            }
        };

        /**
         * Handles a click to a Custom pricing policy enable button.
         *
         * @param {HTMLElement} btn
         */
        const handlePolicySwitchButton = (btn) => {
            const pricingSection = templateService.getComponent('pl-add-price-section'),
                pricesSection = templateService.getComponent('pl-concrete-prices-section'),
                pricingPoliciesSection = pricesSection.querySelector('#pl-pricing-policies'),
                firstServiceDescription = templateService.getComponent('pl-first-service-description'),
                addServiceButton = pricingSection.querySelector('button'),
                pricingPolicies = isMultistore ? this.getSystemPricingPolicies() : serviceModel.pricingPolicies;

            if (btn.classList.contains('pl-selected')) {
                utilityService.showElement(pricingSection);
                if (pricingPolicies.length > 0) {
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
         * Sets pricing policies section.
         */
        const setPricingPolicies = () => {
            const pricesSection = templateService.getComponent('pl-concrete-prices-section'),
                pricingPolicies = pricesSection.querySelector('#pl-pricing-policies'),
                addServiceButton = document.querySelector('#pl-add-price-section button'),
                pricingSection = templateService.getComponent('pl-add-price-section');

            utilityService.showElement(pricingSection);

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

            setPacklinkPriceWrapper();
        };

        /**
         * Sets Packlink price wrapper.
         */
        const setPacklinkPriceWrapper = function () {
            let usePacklinkPriceWrapper = templateService.getComponent('pl-use-packlink-price-wrapper'),
                packlinkPriceLabel = usePacklinkPriceWrapper.querySelector('label'),
                usePacklinkRangeLabel = usePacklinkPriceWrapper.querySelector('#pl-use-packlink-range'),
                useFixedPriceLabel = usePacklinkPriceWrapper.querySelector('#pl-use-fixed-price');

            utilityService.showElement(usePacklinkPriceWrapper);
            packlinkPriceLabel.innerText = misconfigurationDetected
                ? useFixedPriceLabel.innerText
                : usePacklinkRangeLabel.innerText;
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
                misconfigurationDetected: misconfigurationDetected,
                isMultistore: isMultistore,
                systemInfo: systemInfo,
                onSave: onSave
            });

            return false;
        };

        /**
         * Deletes pricing policy from memory.
         *
         * @param {Event} event
         * @param {number} i
         *
         * @returns {boolean}
         */
        const deletePricingPolicy = (event, i) => {
            event.preventDefault();
            let pricingPolicies = this.getSystemPricingPolicies()
            pricingPolicies.splice(i, 1);
            this.setSystemPricingPolicies(pricingPolicies);
            onSave(serviceModel);
            return false;
        };

        /**
         * Renders pricing policies.
         */
        const renderPricingPolicies = () => {
            const pricesSection = templateService.getComponent('pl-concrete-prices-section');
            const pricingPoliciesContainer = pricesSection.querySelector('#pl-pricing-policies');
            const parent = pricingPoliciesContainer.querySelector('.pl-pricing-policies');
            let pricingPolicies = isMultistore ? this.getSystemPricingPolicies() : serviceModel.pricingPolicies;
            parent.innerHTML = '';

            pricingPoliciesContainer.classList.remove('pl-hidden');
            pricingPolicies.forEach((policy, index) => {
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

                if (misconfigurationDetected && policy.pricing_policy.toString() !== pricingPolicyTypes.fixed) {
                    itemEl.querySelector('#pl-price-policy-range-wrapper span').classList.add('pl-invalid-policy');
                }

                if (misconfigurationDetected) {
                    itemEl.innerHTML = itemEl.innerHTML.replaceAll('€', systemInfo.symbols[systemInfo.currencies[0]]);
                } else {
                    itemEl.innerHTML = itemEl.innerHTML.replaceAll('€', systemInfo.symbols[serviceModel.currency]);
                }
            });
        };

        /**
         * Returns range type label.
         *
         * @param {ShippingPricingPolicy} policy
         *
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
         * Returns range type label.
         *
         * @param {ShippingPricingPolicy} policy
         *
         * @returns {string}
         */
        const getPricingPolicyLabel = (policy) => {
            let result = translator.translate('shippingServices.packlinkPrice');
            if (policy.pricing_policy.toString() === pricingPolicyTypes.percent) {
                result = translator.translate('' +
                    'shippingServices.percentagePacklinkPricesWithData',
                    [translator.translate('shippingServices.' + (policy.increase ? 'increase' : 'reduce')), policy.change_percent]
                );
            } else if (policy.pricing_policy.toString() === pricingPolicyTypes.fixed) {
                result = translator.translate('shippingServices.fixedPricesWithData', [policy.fixed_price]);
            }

            return result;
        };

        /**
         * Sets a fixed price based on whether the module is in multistore or not.
         *
         * @param {float} price
         */
        const setFixedPrice = (price) => {
            serviceModel.fixedPrices[systemInfo.system_id] = price;
        }

        /**
         * Returns a fixed price based on whether the module is in multistore or not.
         *
         * @returns {float}
         */
        const getFixedPrice = () => {
            if (systemInfo.system_id in serviceModel.fixedPrices) {
                return serviceModel.fixedPrices[systemInfo.system_id];
            }

            return null;
        }
    }

    Packlink.SingleStorePricePolicyController = SingleStorePricePolicyController;
})();
