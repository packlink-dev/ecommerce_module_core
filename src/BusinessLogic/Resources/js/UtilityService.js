var Packlink = window.Packlink || {};

(function () {
    function UtilityService() {
        /**
         * Adds proper event listeners to input fields in order to allow input filed label translation.
         */
        this.configureInputElements = function () {
            let inputContainers = document.getElementsByClassName('pl-text-input');

            for (let container of inputContainers) {
                let input = container.getElementsByTagName('input')[0];
                textInputTransformLabel(input);
                input.addEventListener('focus', textInputFocusHandler, true);
                input.addEventListener('focusout', textInputFocusHandler, true);
            }
        };

        /**
         * Enables input mask. This mask disables input fields, buttons, checkboxes etc.
         * Mask has z-index of 100, therefore an element that has to be excluded from input mask
         * has to have z-index greater than 100;
         */
        this.enableInputMask = function () {
            document.getElementById('pl-input-mask').classList.add('enabled');
        };

        /**
         * Disables input mask.
         */
        this.disableInputMask = function () {
            document.getElementById('pl-input-mask').classList.remove('enabled');
        };

        /**
         * Enables loading spinner.
         */
        this.showSpinner = function () {
            document.getElementById('pl-spinner').classList.add('enabled');
        };

        /**
         * Hides loading spinner.
         */
        this.hideSpinner = function () {
            document.getElementById('pl-spinner').classList.remove('enabled');
        };

        /**
         * Shows flash message.
         *
         * @note Only one flash message will be shown at the same time.
         *
         * @param {string} message
         * @param {'danger' | 'warning' | 'success'} status
         */
        this.showFlashMessage = function (message, status) {
            let statuses = [
                'warning',
                'danger',
                'success'
            ];

            let messageNode = document.getElementById('pl-flash-message');
            messageNode.classList.remove(...statuses);
            messageNode.classList.add(status);

            let textNode = document.getElementById('pl-flash-message-text');
            textNode.innerHTML = message;

            let hideHandler = function () {
                messageNode.style.display = 'none';
            };

            let closeButton = document.getElementById('pl-flash-message-close-btn');
            closeButton.addEventListener('click', hideHandler, true);

            messageNode.style.display = 'flex';
        };

        /**
         * Creates deep clone of an object with object's properties.
         * Removes object's methods.
         *
         * @note Object cannot have values that cannot be converted to json (undefined, infinity etc).
         *
         * @param {object} obj
         * @return {object}
         */
        this.cloneObject = function (obj) {
            return JSON.parse(JSON.stringify(obj));
        };

        /**
         * Debounces function.
         *
         * @param {number} delay
         * @param {function} target
         * @return {Function}
         */
        this.debounce = function (delay, target) {
            let timerId;
            return function (...args) {
                if (timerId) {
                    clearTimeout(timerId);
                }

                timerId = setTimeout(function () {
                    target(...args);
                    timerId = null;
                }, delay);
            };
        };

        /**
         * Adds given character as a prefix to the given input so that result always has the same string length.
         * If the input is "56", length is 4 and character is "0", resulting string will be "0056".
         * If the input is "P", length is 2 and character is "-" resulting string will be "-4".
         * If the input is "40", length is 2, resulting string will be "40".
         * If the input is "TEXT", length is 2, resulting string will be "TEXT".
         *
         * @param input A string or number to pad.
         * @param {number} length Total length of the final string.
         * @param {string} character The character to pad to the beginning. Defaults to "0".
         *
         * @returns {string} Padded string.
         */
        this.pad = function (input, length, character) {
            let prefix = '';
            for (let i = 0; i < length; i++) {
                prefix += character ? character : '0';
            }

            return (prefix + input).slice(length * -1);
        };

        /** PRIVATE METHODS **/
        /**
         * @param {Event} event
         */
        function textInputFocusHandler(event) {
            textInputTransformLabel(event.target);
        }

        /**
         * @param input
         */
        function textInputTransformLabel(input) {
            let isSelected = document.activeElement === input || input.value,
                spans = input.parentNode.getElementsByTagName('span');

            if (spans && spans.length > 0) {
                spans[0].className = 'pl-text-input-label' + (isSelected ? ' selected' : '');
            }
        }
    }

    Packlink.utilityService = new UtilityService();
})();