if (!window.Packlink) {
    window.Packlink = {};
}

(function () {
    /**
     * @typedef ValidationMessage
     * @property {string} code The message code.
     * @property {string} field The field name that the error is related to.
     * @property {string} message The error message.
     */

    const inputType = {
        number: 'number',
        email: 'email',
        phone: 'phone',
        password: 'password',
        text: 'text'
    };

    const validationRule = {
        numeric: 'numeric',
        integer: 'integer',
        greaterThanZero: 'greaterThanZero',
        nonNegative: 'nonNegative'
    };

    /**
     * The ValidationService constructor.
     *
     * @constructor
     */
    function ValidationService() {
        const translationService = Packlink.translationService,
            templateService = Packlink.templateService,
            utilityService = Packlink.utilityService;

        /**
         * Sets form validation.
         *
         * @param {HTMLElement} form
         * @param {string[]} fields
         */
        this.setFormValidation = (form, fields) => {
            for (const field of fields) {
                let input = form[field];
                input.addEventListener('blur', (event) => {
                    // noinspection JSCheckFunctionSignatures
                    this.validateInputField(event.target);
                }, true);
                input.addEventListener('input', (event) => {
                    // noinspection JSCheckFunctionSignatures
                    this.removeError(event.target);
                }, true);
            }
        };

        /**
         * Validates form. Validates all input and select elements by using data attributes as rules.
         *
         * @param {Element} form
         * @param {string[]} excludedElementNames
         * @return {boolean}
         */
        this.validateForm = (form, excludedElementNames = []) => {
            const inputElements = utilityService.toArray(form.getElementsByTagName('input')),
                selects = utilityService.toArray(form.getElementsByTagName('select')),
                inputs = inputElements.concat(selects),
                length = inputs.length;

            let result = true;

            for (let i = 0; i < length; i++) {
                if (excludedElementNames.indexOf(inputs[i].name) >= 0) {
                    continue;
                }
                result &= this.validateInputField(inputs[i]);
            }

            return result;
        };

        /**
         * Validates a single input element based on the element type and validation rules.
         * Adds a validation error if needed.
         *
         * @param {Element|HTMLInputElement} input
         * @return {boolean}
         */
        this.validateInputField = (input) => {
            this.removeError(input);

            const data = input.dataset;

            if (data.required !== undefined && !this.validateRequiredField(input)) {
                return false;
            }

            switch (data.type) {
                case inputType.number:
                    return this.validateNumber(input);
                case inputType.email:
                    return this.validateEmail(input);
                case inputType.phone:
                    return this.validatePhone(input);
                case inputType.password:
                    return this.validatePasswordLength(input);
                case inputType.text:
                    return this.validateMaxLength(input);
            }

            return true;
        };

        /**
         * Validates if the input has a value. If the value is not set, adds the error mark on field.
         *
         * @param {HTMLInputElement|HTMLSelectElement} input
         * @return {boolean}
         */
        this.validateRequiredField = (input) => validateField(
            input,
            input.value === '' || (input.type === 'checkbox' && !input.checked),
            'validation.requiredField'
        );

        /**
         * Validates a numeric input.
         *
         * @param {HTMLInputElement} input
         * @return {boolean} Indication of the validity.
         */
        this.validateNumber = (input) => {
            const ruleset = input.dataset.validationRule ? input.dataset.validationRule.split(',') : [],
                length = ruleset.length;

            if (!validateField(input, isNaN(input.value), 'validation.' + validationRule.numeric)) {
                return false;
            }

            const value = +input.value;
            for (let i = 0; i < length; i++) {
                const rule = ruleset[i];
                let condition = false;
                switch (rule) {
                    case validationRule.integer:
                        condition = Number.isInteger(value);
                        break;
                    case validationRule.greaterThanZero:
                        condition = value > 0;
                        break;
                    case validationRule.nonNegative:
                        condition = value >= 0;
                        break;
                    default:
                        continue;
                }

                if (!validateField(input, !condition, 'validation.' + rule)) {
                    // break on first rule
                    return false;
                }
            }

            return true;
        };

        /**
         * Validates if the input is a valid email. If not, adds the error mark on field.
         *
         * @param {HTMLInputElement} input
         * @return {boolean}
         */
        this.validateEmail = (input) => {
            let regex = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;

            return validateField(
                input,
                !regex.test(String(input.value).toLowerCase()),
                'validation.invalidEmail'
            );
        };

        /**
         * Validates if the input is a valid phone number. If not, adds the error mark on field.
         *
         * @param {HTMLInputElement} input
         * @return {boolean}
         */
        this.validatePhone = (input) => {
            let regex = /^( |\+|\/|\.\|-|\(|\)|\d)+$/m;

            return validateField(
                input,
                !regex.test(String(input.value).toLowerCase()),
                'validation.invalidPhone'
            );
        };

        /**
         * Validates if the input field has enough characters. If not, adds the error mark on field.
         *
         * @param {HTMLInputElement} input
         * @return {boolean}
         */
        this.validatePasswordLength = (input) => validateField(
            input,
            input.value.length < input.dataset.minLength,
            'validation.shortPassword',
            [input.dataset.minLength]
        );

        /**
         * Validates if the input field is longer than a specified number of characters.
         * If so, adds the error mark on field.
         *
         * @param {HTMLInputElement} input
         * @return {boolean}
         */
        this.validateMaxLength = (input) => validateField(
            input,
            input.dataset.maxLength && input.value.length > input.dataset.maxLength,
            'validation.maxLength',
            [input.dataset.maxLength]
        );

        /**
         * Handles validation errors. These errors come from the back end.
         * @param {ValidationMessage[]} errors
         */
        this.handleValidationErrors = (errors) => {
            for (const error of errors) {
                this.markFieldGroupInvalid('[name=' + error.field + ']', error.message);
            }
        };

        /**
         * Marks a field as invalid.
         *
         * @param {string} fieldSelector The field selector.
         * @param {string} message The message to display.
         * @param {Element} [parent] A parent element.
         */
        this.markFieldGroupInvalid = (fieldSelector, message, parent) => {
            if (!parent) {
                parent = templateService.getMainPage();
            }

            const inputEl = parent.querySelector(fieldSelector);
            if (!inputEl) {
                utilityService.showFlashMessage(message + '. Field: ' + fieldSelector, 'danger', 7000);
            } else {
                this.setError(inputEl, message);

            }
        };

        /**
         * Sets error for an input.
         *
         * @param {Element} input
         * @param {string} message
         */
        this.setError = (input, message) => {
            this.removeError(input);

            let errorTemplate = document.createElement('div');
            input.setAttribute('data-pl-contains-errors', '1');
            errorTemplate.innerHTML = templateService.getComponent('pl-error-template').innerHTML;
            errorTemplate.firstElementChild.innerHTML = message;
            input.after(errorTemplate.firstElementChild);
            input.parentElement.classList.add('pl-error');
        };

        /**
         * Removes error from input form group element.
         *
         * @param {Element} input
         */
        this.removeError = (input) => {
            let errorElement = input.parentNode.querySelector('.pl-error-message');
            if (errorElement) {
                input.parentNode.removeChild(errorElement);
            }

            input.removeAttribute('data-pl-contains-errors');
            input.parentElement.classList.remove('pl-error');
        };

        /**
         * Validates the condition against the input field and marks field invalid if the error condition is met.
         *
         * @param {Element} element
         * @param {boolean} errorCondition Error condition.
         * @param {string} errorCode
         * @param {*[]} [errorParams]
         *
         * @return {boolean}
         */
        const validateField = (element, errorCondition, errorCode, errorParams) => {
            if (errorCondition) {
                this.setError(element, translationService.translate(errorCode, errorParams));

                return false;
            }

            return true;
        };
    }

    Packlink.validationService = new ValidationService();
})();