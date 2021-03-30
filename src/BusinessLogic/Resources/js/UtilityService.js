if (!window.Packlink) {
    window.Packlink = {};
}

(function () {
    function UtilityService() {
        /**
         * Shows the HTML node.
         *
         * @param {HTMLElement} element
         */
        this.showElement = (element) => {
            element.classList.remove('pl-hidden');
        };

        /**
         * Hides the HTML node.
         *
         * @param {HTMLElement} element
         */
        this.hideElement = (element) => {
            element.classList.add('pl-hidden');
        };

        /**
         * Enables loading spinner.
         */
        this.showSpinner = () => {
            this.showElement(document.getElementById('pl-spinner'));
        };

        /**
         * Hides loading spinner.
         */
        this.hideSpinner = () => {
            this.hideElement(document.getElementById('pl-spinner'));
        };

        /**
         * Shows flash message.
         *
         * @note Only one flash message will be shown at the same time.
         *
         * @param {string} message
         * @param {'danger' | 'warning' | 'success'} status
         * @param {number} [clearAfter] Time in ms to remove alert message.
         */
        this.showFlashMessage = (message, status, clearAfter) => {
            let messageNode = document.createElement('div');
            messageNode.innerHTML = Packlink.templateService.getComponent('pl-alert').innerHTML;
            messageNode = messageNode.firstElementChild;

            const alertBox = messageNode.querySelector('.pl-alert');

            alertBox.classList.add('pl-alert-' + status);

            let textNode = messageNode.querySelector('.pl-alert-text');
            textNode.innerHTML = message;

            const hideHandler = () => {
                messageNode.remove();
            };

            if (clearAfter) {
                setTimeout(hideHandler, clearAfter);
            }

            messageNode.addEventListener('click', hideHandler, true);

            Packlink.templateService.getMainPage().appendChild(messageNode);
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
        this.cloneObject = (obj) => JSON.parse(JSON.stringify(obj));

        /**
         * Debounces function.
         *
         * @param {number} delay
         * @param {function(...)} target
         * @param {...} target function args.
         * @return {function(...)}
         */
        this.debounce = (delay, target) => {
            let timerId;
            return (...args) => {
                if (timerId) {
                    clearTimeout(timerId);
                }

                timerId = setTimeout(() => {
                    target(...args);
                    timerId = null;
                }, delay);
            };
        };

        /**
         * Adds given character as a prefix to the given input so that result always has the same string length.
         * If the input is "56", length is 4 and character is "0", resulting string will be "0056".
         * If the input is "P", length is 2 and character is "-" resulting string will be "-P".
         * If the input is "40", length is 2, resulting string will be "40".
         * If the input is "TEXT", length is 2, resulting string will be "TEXT".
         *
         * @param {number|string} input A string or number to pad.
         * @param {number} length Total length of the final string.
         * @param {string} character The character to pad to the beginning. Defaults to "0".
         *
         * @returns {string} Padded string.
         */
        this.pad = (input, length, character) => {
            let prefix = '';
            for (let i = 0; i < length; i++) {
                prefix += character ? character : '0';
            }

            return (prefix + input).slice(length * -1);
        };

        /**
         * Converts a collection to array.
         *
         * @param collection
         * @return {[]}
         */
        this.toArray = (collection) => {
            if (Array.prototype.from) {
                return Array.from(collection);
            }

            const result = [],
                length = collection.length;

            for (let i = 0; i < length; i++) {
                result.push(collection[i]);
            }

            return result;
        };
    }

    Packlink.utilityService = new UtilityService();
})();