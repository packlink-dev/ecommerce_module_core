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
            templateId = 'pl-register-page';

        let form;

        /**
         * Displays page content.
         */
        this.display = function () {
            templateService.setCurrentTemplate(templateId);

            ajaxService.get(configuration.getRegistrationData, populateInitialValues);

            utilityService.configureInputElements();

            const registerPage = templateService.getMainPage();

            form = templateService.getComponent('pl-register-form', registerPage);
            form.addEventListener('submit', register);
            templateService.getComponent('pl-go-to-login', registerPage).addEventListener('click', goToLogin);

            templateService.getComponent('pl-register-platform-country', registerPage).value =
                Packlink.models.hasOwnProperty('country') ? Packlink.models.country : 'ES';

            initEmailField();
            initPasswordField();
            initPhoneField();
            initSelectBox();
            initTermsAndConditionCheckbox();

            validateForm();
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

        function initEmailField() {
            let input = templateService.getComponent('pl-register-email');

            input.addEventListener('blur', function () {
                if (!validateRequiredField(input) || !validateEmail(input)) {
                    input.setAttribute('data-pl-contains-errors', '1');
                }

                validateForm();
            }, true);

            clearErrors(input);
        }

        function initPasswordField() {
            let input = templateService.getComponent('pl-register-password');

            input.addEventListener('blur', function () {
                if (!validateRequiredField(input) || !validatePasswordLength(input)) {
                    input.setAttribute('data-pl-contains-errors', '1');
                }

                validateForm();
            }, true);

            clearErrors(input);
        }

        function initPhoneField() {
            let input = templateService.getComponent('pl-register-phone');

            input.addEventListener('blur', function () {
                if (!validateRequiredField(input) || !validatePhone(input)) {
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
                if (!validateRequiredField(input)) {
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
            let checkbox = templateService.getComponent('pl-register-terms-and-conditions');

            checkbox.addEventListener('change', function () {
                validateForm();
            });
        }

       function validateRequiredField(input) {
            if (input.value === '') {
                templateService.setError(input, translationService.translate('register.requiredField'));

                return false;
            }

            return true;
        }

        function validateEmail(input) {
            let regex = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;

            if (!regex.test(String(input.value).toLowerCase())) {
                templateService.setError(input, translationService.translate('register.invalidEmail'));

                return false;
            }

            return true;
        }

        function validatePasswordLength(input) {
            if (input.value.length < 6) {
                templateService.setError(input, translationService.translate('register.shortPassword'));

                return false;
            }

            return true;
        }

        function validatePhone(input) {
            let regex = /^(\ |\+|\/|\.\|-|\(|\)|\d)+$/m;

            if (!regex.test(String(input.value).toLowerCase())) {
                templateService.setError(input, translationService.translate('register.invalidPhone'));

                return false;
            }

            return true;
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
                if (input.hasAttribute('data-pl-contains-errors') || input.value === '') {
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
