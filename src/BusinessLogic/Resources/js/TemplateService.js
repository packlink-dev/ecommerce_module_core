var Packlink = window.Packlink || {};

(function () {
    function TemplateService() {
        /**
         * Retrieves template children by template id.
         *
         * @param {string} template
         * @return {HTMLCollection | null}
         */
        this.getTemplate = function (template) {
            let temp = document.getElementById(template);

            if (!temp) {
                return null;
            }

            let clone = temp.cloneNode(true);

            return clone.children;
        };

        /**
         * Retrieves component by it's id or attribute.
         *
         * @param {string} component
         * @param {Element} [element]
         * @param {string|int} [attribute]
         *
         * @return {Element}
         */
        this.getComponent = function (component, element, attribute) {
            if (typeof element === 'undefined') {
                return document.getElementById(component);
            }

            if (typeof attribute === 'undefined') {
                return element.querySelector('#' + component);
            }

            return element.querySelector('[' + component + '="' + attribute + '"]');
        };

        /**
         * Retrieves all nodes with specified attribute.
         *
         * @param {string} attribute
         * @param {Element} [element]
         *
         * @return {NodeListOf<Element>}
         */
        this.getComponentsByAttribute = function (attribute, element) {
            let selector = '[' + attribute + ']';

            if (typeof element === 'undefined') {
                return document.querySelectorAll(selector);
            }

            return element.querySelectorAll(selector);
        };

        /**
         * Changes currently active page.
         *
         * @param {string} template
         * @param {string} [extensionPointIdentifier]
         * @param {boolean} [clearExtensionPoint=true]
         *
         * @return {Element}
         */
        this.setTemplate = function (template, extensionPointIdentifier, clearExtensionPoint) {
            if (typeof extensionPointIdentifier === 'undefined') {
                extensionPointIdentifier = 'pl-content-extension-point';
            }

            if (typeof clearExtensionPoint === 'undefined') {
                clearExtensionPoint = true;
            }

            let extensionPoint = this.getComponent(extensionPointIdentifier);

            if (clearExtensionPoint) {
                this.clearComponent(extensionPoint);
            }

            let templateElements = this.getTemplate(template);
            while (templateElements && templateElements.length) {
                extensionPoint.appendChild(templateElements[0]);
            }

            return extensionPoint;
        };

        /**
         * Removes component's children.
         *
         * @param {Element} component
         */
        this.clearComponent = function (component) {
            while (component.firstChild) {
                component.removeChild(component.firstChild);
            }
        };

        /**
         * Sets error for input.
         *
         * @param {Element} input
         * @param {string} message
         */
        this.setError = function (input, message) {
            this.removeError(input);

            let errorTemplate = this.getTemplate('pl-error-template')[0];
            let msgField = this.getComponent('pl-error-text', errorTemplate);
            msgField.innerHTML = message;
            input.after(errorTemplate);
            input.classList.add('pl-error');
        };

        /**
         * Removes error from input element.
         *
         * @param {Element} input
         */
        this.removeError = function (input) {
            let firstSibling = input.nextSibling;
            if (firstSibling && firstSibling.getAttribute && firstSibling.getAttribute('data-pl-element') === 'error') {
                firstSibling.remove();
            }

            input.classList.remove('pl-error');
        };
    }

    Packlink.templateService = new TemplateService();
})();