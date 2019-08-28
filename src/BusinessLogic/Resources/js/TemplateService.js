var Packlink = window.Packlink || {};

(function () {
    function TemplateService() {
        this.getComponent = getComponent;
        this.getTemplate = getTemplate;
        this.getComponentsByAttribute = getComponentsByAttribute;
        this.setTemplate = setTemplate;
        this.clearComponent = clearComponent;
        this.setError = setError;
        this.removeError = removeError;

        /**
         * Retrieves template children by template id.
         *
         * @param {string} template
         * @return {HTMLCollection | null}
         */
        function getTemplate(template) {
            let temp = document.getElementById(template);

            if (!temp) {
                return null;
            }

            let clone = temp.cloneNode(true);

            return clone.children;
        }

        /**
         * Retrieves component by it's id or attribute.
         *
         * @param {string} component
         * @param {Element} [element]
         * @param {string|int} [attribute]
         *
         * @return {Element}
         */
        function getComponent(component, element, attribute) {
            if (typeof element === 'undefined') {
                return document.getElementById(component);
            }

            if (typeof attribute === 'undefined') {
                return element.querySelector(`#${component}`);
            }

            return element.querySelector(`[${component}="${attribute}"]`);
        }

        /**
         * Retrieves all nodes with specified attribute.
         *
         * @param {string} attribute
         * @param {Element} [element]
         *
         * @return {NodeListOf<Element>}
         */
        function getComponentsByAttribute(attribute, element) {
            let selector = '[' + attribute + ']';

            if (typeof element === "undefined") {
                return document.querySelectorAll(selector);
            }

            return element.querySelectorAll(selector);
        }

        /**
         * Changes currently active page.
         *
         * @param {string} template
         * @param {string} [extensionPointIdentifier]
         * @param {boolean} [clearExtensionPoint=true]
         *
         * @return {Element}
         */
        function setTemplate(template, extensionPointIdentifier, clearExtensionPoint) {
            if (typeof extensionPointIdentifier === 'undefined') {
                extensionPointIdentifier = 'pl-content-extension-point';
            }

            if (typeof clearExtensionPoint === 'undefined') {
                clearExtensionPoint = true;
            }

            let extensionPoint = getComponent(extensionPointIdentifier);

            if (clearExtensionPoint) {
                clearComponent(extensionPoint);
            }

            let templateElements = getTemplate(template);
            while (templateElements && templateElements.length) {
                extensionPoint.appendChild(templateElements[0]);
            }

            return extensionPoint;
        }

        /**
         * Removes component's children.
         *
         * @param {Element} component
         */
        function clearComponent(component) {
            while (component.firstChild) {
                component.removeChild(component.firstChild);
            }
        }

        /**
         * Sets error for input.
         *
         * @param {Element} input
         * @param {string} message
         */
        function setError(input, message) {
            removeError(input);

            let errorTemplate = getTemplate('pl-error-template')[0];
            let msgField = getComponent('pl-error-text', errorTemplate);
            msgField.innerHTML = message;
            input.after(errorTemplate);
            input.classList.add('pl-error');
        }

        /**
         * Removes error from input element.
         *
         * @param input
         */
        function removeError(input) {
            let firstSibling = input.nextSibling;
            if (firstSibling && firstSibling.getAttribute && firstSibling.getAttribute('data-pl-element') === 'error') {
                firstSibling.remove();
            }
            input.classList.remove('pl-error');
        }
    }

    Packlink.templateService = new TemplateService();
})();