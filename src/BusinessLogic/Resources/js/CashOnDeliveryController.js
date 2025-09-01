if (!window.Packlink) {
    window.Packlink = {};
}

(function () {
    /**
     * @param {{getUrl: string}} configuration
     * @constructor
     */
    function CashOnDeliveryController(configuration) {
        const templateService = Packlink.templateService,
            utilityService = Packlink.utilityService,
            validationService = Packlink.validationService,
            ajaxService = Packlink.ajaxService,
            state = Packlink.state;

        this.config = {};
        this.pageId = 'pl-cod-page';

        this.modelFields = [
            'active',
            'accountHolder',
            'iban',
            'cashOnDeliveryFee',
            'offlinePaymentMethod'
        ];

        /**
         * Handles Back button navigation.
         */
        const goToPreviousPage = () => {
            if (this.config.prevState) {
                state.goToState(this.config.prevState);
            }
        };

        /**
         * Displays page content.
         */
        this.display = (displayConfig) => {
            this.config = displayConfig;
            ajaxService.get(configuration.getDataUrl, this.constructPage);
        };

        const constructPaymentMethodDropdown = (response) => {
            let paymentInput = templateService.getComponent('pl-cod-offlinePaymentMethod');

            if (!paymentInput) return;

            paymentInput.innerHTML = '';

            response.forEach((payment, index) => {
                const optionElement = document.createElement('option');
                optionElement.value = payment.name;
                optionElement.innerText = payment.displayName;

                if (index === 0) {
                    optionElement.selected = true;
                }

                paymentInput.appendChild(optionElement);
            });
        };


        /**
         * Constructs page after ajax response.
         */
        this.constructPage = (response) => {
            templateService.setCurrentTemplate(this.pageId);

            let mainPage = templateService.getMainPage(),
                backButton = mainPage.querySelector('.pl-sub-header button');

            if (backButton) {
                backButton.addEventListener('click', goToPreviousPage);
            }

            constructPaymentMethodDropdown(response.paymentMethods);

            populateCodConfiguration(response.configuration);

            const activeCheckbox = templateService.getComponent('pl-cod-active');
            const configSection = mainPage.querySelector('.pl-config-section');
            const infoBox = mainPage.querySelector('.pl-cod-info-box');

            if (activeCheckbox && configSection) {
                configSection.style.display = activeCheckbox.checked ? 'block' : 'none';

                if (infoBox) {
                    infoBox.style.display = activeCheckbox.checked ? 'flex' : 'none';
                }

                activeCheckbox.addEventListener('change', () => {
                    configSection.style.display = activeCheckbox.checked ? 'block' : 'none';

                    if (infoBox) {
                        infoBox.style.display = activeCheckbox.checked ? 'flex' : 'none';
                    }

                });
            }

            this.setupSubmitButton();

            setTemplateBasedOnState();

            utilityService.hideSpinner();
        };

        /**
         * @param {Object} codConfig
         */
        const populateCodConfiguration = (codConfig) => {
            const mainPage = templateService.getMainPage();
            const configSection = mainPage.querySelector('.pl-config-section');

            if (!codConfig || !configSection) return;

            const activeCheckbox = templateService.getComponent('pl-cod-active');
            if (activeCheckbox) {
                activeCheckbox.checked = !!codConfig.active;
            }

            if (codConfig.active) {
                configSection.style.display = 'block';

                const accountHolderInput = templateService.getComponent('pl-cod-accountHolder');
                if (accountHolderInput) accountHolderInput.value = codConfig.account.accountHolderName || '';

                const ibanInput = templateService.getComponent('pl-cod-iban');
                if (ibanInput) ibanInput.value = codConfig.account.iban || '';

                const feeInput = templateService.getComponent('pl-cod-cashOnDeliveryFee');
                if (feeInput) feeInput.value = codConfig.account.cashOnDeliveryFee != null ? codConfig.account.cashOnDeliveryFee : '';

                const paymentMethodSelect = templateService.getComponent('pl-cod-offlinePaymentMethod');
                if (paymentMethodSelect) paymentMethodSelect.value = codConfig.account.offlinePaymentMethod || '';
            } else {
                configSection.style.display = 'none';
            }
        }

        const setTemplateBasedOnState = () => {
            let mainPage = templateService.getMainPage(),
                page = mainPage.querySelector('.' + this.pageId);


            page.classList.add('pl-page-' + this.config.code);
        };

        this.setupSubmitButton = () => {
            const submitBtn = templateService.getComponent('pl-page-submit-btn');
            if (!submitBtn) return;

            submitBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.saveData();
            });
        }

        this.saveData = () => {

            const form = templateService.getMainPage().querySelector('form');

            if(!validationService.validateForm(form))
            {
                return false;
            }

            const rawValues = this.getFormValues();
            const payload = {
                enabled: true,
                active: !!rawValues.active,
                account: {
                    accountHolderName: rawValues.accountHolder || '',
                    iban: rawValues.iban || '',
                    cashOnDeliveryFee: null,
                    offlinePaymentMethod: rawValues.offlinePaymentMethod || ''
                }
            };

            if (rawValues.cashOnDeliveryFee != null && rawValues.cashOnDeliveryFee !== '') {
                payload.account.cashOnDeliveryFee = parseFloat(rawValues.cashOnDeliveryFee);
            }

            utilityService.showSpinner();
            ajaxService.post(configuration.submitDataUrl, payload, goToNextPage,
                Packlink.responseService.errorHandler);
        };

        /**
         * Handles Save button navigation.
         */
        const goToNextPage = () => {
            state.goToState(this.config.nextState, {
                'code': this.config.code,
                'prevState': this.config.prevState,
                'nextState': 'onboarding-overview',
            });
        };

        this.getFormValues = () => {
            const values = {};
            this.modelFields.forEach(field => {
                const el = templateService.getComponent('pl-cod-' + field);
                if (!el) return;

                if (field === 'active') {
                    values[field] = el.checked;
                } else if (field === 'cashOnDeliveryFee') {
                    values[field] = el.value !== '' ? parseFloat(el.value) : null;
                } else {
                    values[field] = el.value || '';
                }
            });
            return values;
        };

    }

    Packlink.CashOnDeliveryController = CashOnDeliveryController;
})();
