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

    /**
     * The ValidationService constructor.
     *
     * @constructor
     */
    function ValidationService() {
        let translationService = Packlink.translationService,
            templateService = Packlink.templateService;

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
                'validation.requiredField'
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

            this.setError(parent.querySelector(fieldSelector), message);
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