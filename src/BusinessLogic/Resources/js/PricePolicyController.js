if (!window.Packlink) {
    window.Packlink = {};
}

(function () {
    /**
     * @constructor
     */
    function PricePolicyController() {
        const templateService = Packlink.templateService,
            utilityService = Packlink.utilityService,
            translator = Packlink.translationService,
            validationService = Packlink.validationService;

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
         * @param {{
         *  service: ShippingService,
         *  policyIndex: number|null,
         *  misconfigurationDetected: boolean,
         *  isMultistore: boolean,
         *  systemInfo: SystemInfo,
         *  onSave: function(ShippingService)
         * }} config
         */
        this.display = function (config) {
            serviceModel = config.service;
            misconfigurationDetected = config.misconfigurationDetected;
            systemInfo = config.systemInfo;
            isMultistore = config.isMultistore;

            const policyIndex = config.policyIndex,
                currentPolicy = policyIndex !== null ? serviceModel.pricingPolicies[policyIndex] : null;

            let template = templateService.getTemplate('pl-pricing-policy-modal'),
                systemCurrency = systemInfo.currencies[0],
                currencySymbol = systemInfo.symbols[misconfigurationDetected ? systemCurrency : serviceModel.currency];

            // noinspection JSCheckFunctionSignatures
            const modal = new Packlink.modalService({
                content: template.replaceAll('€', currencySymbol),
                canClose: false,
                buttons: [
                    {
                        title: translator.translate('general.save'),
                        primary: true,
                        onClick: () => {
                            savePricingPolicy(currentPolicy, policyIndex, modal, config.onSave);
                        }
                    },
                    {
                        title: translator.translate('general.cancel'),
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
        };

        /**
         * Saves pricing policy
         * @param {ShippingPricingPolicy | null} currentPolicy
         * @param {number} policyIndex
         * @param {ModalService} modal
         * @param {function(ShippingService)} onSave
         */
        const savePricingPolicy = (currentPolicy, policyIndex, modal, onSave) => {
            const form = templateService.getComponent('pl-pricing-policy-form');
            if (!validatePricingPolicyForm(form)) {
                return;
            }

            let pricingPolicy = {};
            pricingPolicyModelFields.forEach(function (field) {
                pricingPolicy[field] = form[field].value !== '' ? form[field].value : null;
            });

            pricingPolicy.increase = form['increase'].checked;
            pricingPolicy.system_id = systemInfo.system_id;
            removeUnneededFieldsFromModel(pricingPolicy);

            if (currentPolicy === null) {
                serviceModel.pricingPolicies.push(pricingPolicy);
            } else {
                serviceModel.pricingPolicies[policyIndex] = pricingPolicy;
            }

            onSave(serviceModel);
            modal.close();
        };

        /**
         * Removes not needed fields from model.
         *
         * @param {ShippingPricingPolicy} pricingPolicy
         */
        const removeUnneededFieldsFromModel = (pricingPolicy) => {
            if (pricingPolicy.range_type.toString() === rangeTypes.price) {
                pricingPolicy.from_weight = null;
                pricingPolicy.to_weight = null;
            } else if (pricingPolicy.range_type.toString() === rangeTypes.weight) {
                pricingPolicy.from_price = null;
                pricingPolicy.to_price = null;
            }

            if (pricingPolicy.pricing_policy.toString() !== pricingPolicies.percent) {
                pricingPolicy.change_percent = null;

            } else if (pricingPolicy.pricing_policy.toString() !== pricingPolicies.fixed) {
                pricingPolicy.fixed_price = null;
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

            if (misconfigurationDetected) {
                pricingPolicyForm['pricing_policy'].value = pricingPolicies.fixed;
                pricingPolicyForm['pricing_policy'].disabled = true;
                if (pricingPolicy !== null) {
                    validationService.validateInputField(pricingPolicyForm['fixed_price']);
                    validationService.validateRequiredField(pricingPolicyForm['fixed_price']);
                }
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

            validationService.setFormValidation(pricingPolicyForm, pricingPolicyModelFields);
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

            if (priceRangeSelect.value === rangeTypes.price) {
                utilityService.hideElement(fromToWeightWrapper);
            } else if (priceRangeSelect.value === rangeTypes.weight) {
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

            if (pricingPolicySelect.value === pricingPolicies.percent) {
                utilityService.showElement(pricePercentageWrapper);
            } else if (pricingPolicySelect.value === pricingPolicies.fixed) {
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
            const rangeType = pricingPolicy['range_type'].value,
                currentPricingPolicy = pricingPolicy['pricing_policy'].value;

            if (!validateRange(rangeType, pricingPolicy['from_price'], pricingPolicy['to_price'], rangeTypes.price) ||
                !validateRange(rangeType, pricingPolicy['from_weight'], pricingPolicy['to_weight'], rangeTypes.weight)
            ) {
                return false;
            }

            if (currentPricingPolicy === pricingPolicies.percent) {
                if (!validationService.validateRequiredField(pricingPolicy['change_percent']) ||
                    !validationService.validateNumber(pricingPolicy['change_percent'])) {
                    return false;
                }

                if (!pricingPolicy['increase'].checked && pricingPolicy['change_percent'].value > 99) {
                    validationService.setError(pricingPolicy['change_percent'], translator.translate('validation.invalidMaxValue', [99]))
                    return false;
                }
            }

            return !(currentPricingPolicy === pricingPolicies.fixed
                && (!validationService.validateRequiredField(pricingPolicy['fixed_price'])
                    || !validationService.validateNumber(pricingPolicy['fixed_price'])));
        };

        /**
         * Validates the given range.
         *
         * @param {string} currentRangeType
         * @param {HTMLInputElement} fromRange
         * @param {HTMLInputElement} toRange
         * @param {string} rangeTypeCondition
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
    }

    Packlink.PricePolicyController = PricePolicyController;
})();
