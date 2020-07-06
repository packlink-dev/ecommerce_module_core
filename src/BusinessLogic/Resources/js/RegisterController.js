if (!window.Packlink) {
    window.Packlink = {};
}

(function () {
    /**
     * Handles register page logic.
     *
     * @param {{getRegistrationData: string, submit: string}} configuration
     * @constructor
     */
    function RegisterController(configuration) {

        const templateService = Packlink.templateService,
            ajaxService = Packlink.ajaxService,
            state = Packlink.state,
            utilityService = Packlink.utilityService,
            translationService = Packlink.translationService,
            validationService = Packlink.validationService,
            templateId = 'pl-register-page';

        let form,
            country;

        /**
         * The main entry point for controller.
         */
        this.display = additionalConfig => {
            templateService.setCurrentTemplate(templateId);
            country = additionalConfig.hasOwnProperty('country') ? additionalConfig.country : 'ES';

            ajaxService.get(configuration.getRegistrationData, populateInitialValues);

            const registerPage = templateService.getMainPage();

            form = templateService.getComponent('pl-register-form', registerPage);
            form.addEventListener('submit', register);

            templateService.getComponent('pl-go-to-login', registerPage).addEventListener('click', goToLogin);

            templateService.getComponent('pl-register-platform-country', registerPage).value = country;

            initInputField('pl-register-email', validationService.validateEmail);
            initInputField('pl-register-password', validationService.validatePasswordLength);
            initInputField('pl-register-phone', validationService.validatePhone);
            initInputField('pl-register-shipment-volume');
            initInputField('pl-register-terms-and-conditions');
        };

        /**
         * Populates initial values from the backend.
         *
         * @param {{
         *  email: string,
         *  phone: string,
         *  source: string,
         *  termsAndConditionsUrl: string,
         *  privacyPolicyUrl: string
         *  }} response
         */
        const populateInitialValues = response => {
            let emailInput = templateService.getComponent('pl-register-email'),
                phoneInput = templateService.getComponent('pl-register-phone'),
                sourceInput = templateService.getComponent('pl-register-source');

            emailInput.value = response.email;
            phoneInput.value = response.phone;
            sourceInput.value = response.source;

            let termsAndConditionsLabel = templateService.getComponent('pl-register-terms-and-conditions-label'),
                termsTranslation = translationService.translate(
                    'register.termsAndConditions',
                    [response.termsAndConditionsUrl, response.privacyPolicyUrl]
                );

            termsAndConditionsLabel.querySelector('label').innerHTML += termsTranslation;
        };

        /**
         * Initializes the input field. Attaches proper event listeners.
         *
         * @param {string} componentSelector
         * @param {*} [specificValidationCallback]
         */
        const initInputField = (componentSelector, specificValidationCallback) => {
            let input = templateService.getComponent(componentSelector);

            input.addEventListener('blur', () => {
                validateInput(input, specificValidationCallback);
                enableSubmit();
            }, true);

            input.addEventListener('input', () => {
                clearErrors(input);
            });

            input.addEventListener('change', () => {
                enableSubmit();
            });
        };

        /**
         * Redirects to login.
         *
         * @param {Event} event
         *
         * @returns {boolean}
         */
        const goToLogin = (event) => {
            event.preventDefault();

            Packlink.state.goToState('login');

            return false;
        };

        /**
         * Validates the input. It checks if the value is set and additionally uses the provided validation function.
         *
         * @param {Element|string} selector
         * @param {function(Element)} [additionalValidation]
         */
        const validateInput = (selector, additionalValidation) => {
            let input = typeof selector === 'string' ? templateService.getComponent(selector) : selector;

            if (!validationService.validateRequiredField(input)
                || (additionalValidation && !additionalValidation(input))
            ) {
                input.setAttribute('data-pl-contains-errors', '1');
            } else {
                clearErrors(input);
            }
        };

        /**
         * Removes errors from the input field.
         *
         * @param {Element} input
         */
        const clearErrors = (input) => {
            if (input.hasAttribute('data-pl-contains-errors')) {
                input.removeAttribute('data-pl-contains-errors');
            }

            validationService.removeError(input);
        };

        /**
         * Enables or disables the submit button.
         */
        const enableSubmit = () => {
            let inputs = form.querySelectorAll('input,select'),
                registerButton = templateService.getComponent('pl-register-button');

            registerButton.disabled = false;
            inputs.forEach(input => {
                if (input.hasAttribute('data-pl-contains-errors')) {
                    registerButton.disabled = true;
                }
            });
        };

        const validateForm = () => {
            validateInput('pl-register-email', validationService.validateEmail);
            validateInput('pl-register-password', validationService.validatePasswordLength);
            validateInput('pl-register-phone', validationService.validatePhone);
            validateInput('pl-register-shipment-volume');
            validateInput('pl-register-terms-and-conditions');

            enableSubmit();
        };

        /**
         * Handles form submit.
         *
         * @param {Event} event
         * @returns {boolean}
         */
        const register = event => {
            event.preventDefault();
            validateForm();

            if (form.querySelectorAll('[data-pl-contains-errors]').length === 0) {
                utilityService.showSpinner();
                ajaxService.post(
                    configuration.submit,
                    {
                        'email': event.target['email'].value,
                        'password': event.target['password'].value,
                        'estimated_delivery_volume': event.target['estimated_delivery_volume'].value,
                        'phone': event.target['phone'].value,
                        'platform_country': event.target['platform_country'].value,
                        'source': event.target['source'].value,
                        'terms_and_conditions': !!event.target['terms_and_conditions'].checked,
                        'marketing_emails': !!event.target['marketing_emails'].checked,
                    },
                    successfulRegister,
                    errorHandler
                );
            }

            return false;
        };

        /**
         * Handles a successful registration request.
         *
         * @param {{success: boolean}} response
         */
        const successfulRegister = response => {
            if (response.success) {
                state.goToState('onboarding-state');
            }
        };

        /**
         * Handles an error response from the register action.
         *
         * @param {{success: boolean, error?: string, messages?: ValidationMessage[]}} response
         */
        const errorHandler = response => {
            utilityService.hideSpinner();
            if (response.error) {
                utilityService.showFlashMessage(response.error, 'danger', 7000);
            } else if (response.messages) {
                validationService.handleValidationErrors(response.messages);
            } else {
                utilityService.showFlashMessage('Unknown error occurred.', 'danger', 7000);
            }
        };
    }

    Packlink.RegisterController = RegisterController;
})();
