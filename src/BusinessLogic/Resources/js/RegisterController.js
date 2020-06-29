var Packlink = window.Packlink || {};

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
         * Displays page content.
         */
        this.display = function (additionalConfig) {
            templateService.setCurrentTemplate(templateId);
            country = additionalConfig.hasOwnProperty('country') ? additionalConfig.country : 'ES';

            ajaxService.get(configuration.getRegistrationData, populateInitialValues);
        };

        function populateInitialValues(response) {
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

            termsAndConditionsLabel.innerHTML += termsTranslation;

            utilityService.configureInputElements();

            const registerPage = templateService.getMainPage();

            form = templateService.getComponent('pl-register-form', registerPage);
            form.addEventListener('submit', register);
            templateService.getComponent('pl-go-to-login', registerPage).addEventListener('click', goToLogin);

            templateService.getComponent('pl-register-platform-country', registerPage).value = country;

            validateRequiredInputField('pl-register-email', validationService.validateEmail);
            validateRequiredInputField('pl-register-password', validationService.validatePasswordLength);
            validateRequiredInputField('pl-register-phone', validationService.validatePhone);
            initSelectBox();
            initTermsAndConditionCheckbox();

            validateForm();
        }

        /**
         * Redirects to login.
         *
         * @param event
         *
         * @returns {boolean}
         */
        function goToLogin(event) {
            event.preventDefault();

            Packlink.state.goToState('login');

            return false;
        }

        function validateRequiredInputField(componentSelector, specificValidationCallback) {
            let input = templateService.getComponent(componentSelector);

            input.addEventListener('blur', function () {
                if (!validationService.validateRequiredField(input) || !specificValidationCallback(input)) {
                    input.setAttribute('data-pl-contains-errors', '1');
                }

                validateForm();
            }, true);

            clearErrors(input);
        }

        function initSelectBox() {
            let container = templateService.getComponent('pl-register-delivery-volume'),
                input = container.getElementsByTagName('select')[0],
                label = container.querySelector('.pl-text-input-label');

            input.addEventListener('blur', function () {
                if (!validationService.validateRequiredField(input)) {
                    label.classList.remove('selected');
                    input.setAttribute('data-pl-contains-errors', '1');
                }

                validateForm();
            });

            input.addEventListener('change', function () {
                validateForm();
            });

            clearErrors(input);
        }

        function clearErrors(input) {
            input.addEventListener('input', function () {
                if (input.hasAttribute('data-pl-contains-errors')) {
                    input.removeAttribute('data-pl-contains-errors');
                }
                templateService.removeError(input);
            });
        }

        function initTermsAndConditionCheckbox() {
            let checkbox = document.getElementById('pl-register-terms-and-conditions');

            checkbox.addEventListener('change', function () {
                validateForm();
            });
        }

        function validateForm() {
            let inputs = form.querySelectorAll('input,select'),
                termsAndConditions = templateService.getComponent('pl-register-terms-and-conditions'),
                registerButton = templateService.getComponent('pl-register-button');

            registerButton.disabled = false;

            if (!termsAndConditions.checked) {
                registerButton.disabled = true;

                return;
            }

            for (let input of inputs) {
                if (input.hasAttribute('data-pl-contains-errors')) {
                    registerButton.disabled = true;

                    break;
                }
            }
        }

        /**
         * Handles form submit.
         * @param event
         * @returns {boolean}
         */
        function register(event) {
            event.preventDefault();

            ajaxService.post(configuration.submit, {
                'email': event.target['email'].value,
                'password': event.target['password'].value,
                'estimated_delivery_volume': event.target['estimated_delivery_volume'].value,
                'phone': event.target['phone'].value,
                'platform_country': event.target['platform_country'].value,
                'source': event.target['source'].value,
                'terms_and_conditions': !!event.target['terms_and_conditions'].checked,
                'marketing_emails': !!event.target['marketing_emails'].checked,
            }, successfulRegister);

            return false;
        }

        function successfulRegister(response) {
            if (response.success) {
                state.goToState('onboarding');
            }
        }
    }

    Packlink.RegisterController = RegisterController;
})();
