var Packlink = window.Packlink || {};

(function () {
    function TemplateService() {
        /**
         * The configuration object for all templates
         * @type {{}}
         */
        let templates = {};
        let mainPlaceholder = '#pl-main-page-holder';

        this.setMainPlaceholder = function (placeholder) {
            mainPlaceholder = placeholder;
        };

        /**
         * Gets the main page DOM element.
         *
         * @returns {Element}
         */
        this.getMainPage = function () {
            return document.querySelector(mainPlaceholder);
        };

        /**
         * Retrieves component by its id or attribute.
         *
         * @param {string} component
         * @param {Element} [element]
         * @param {string|int} [attribute]
         *
         * @return {Element}
         */
        this.getComponent = function (component, element, attribute) {
            if (!element) {
                return document.getElementById(component);
            }

            if (!attribute) {
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

            if (!element) {
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
         * @deprecated Do not use since it is not updated to the latest template format.
         */
        this.setTemplate = function (template, extensionPointIdentifier, clearExtensionPoint) {
            if (!extensionPointIdentifier) {
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
         * Populates the template with the provided HTML for page elements.
         *
         * @param {{}} configuration
         */
        this.setTemplates = function (configuration) {
            templates = configuration;
        };

        /**
         * Sets current template in the page.
         *
         * @param {string} templateId
         */
        this.setCurrentTemplate = function (templateId) {
            for (let [extensionPointId, html] of Object.entries(templates[templateId])) {
                const component = this.getComponent(extensionPointId);

                if (component) {
                    component.innerHTML = html ? Packlink.translationService.translateHtml(html) : '';
                }
            }
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

            let errorTemplate = document.createElement('div');
            errorTemplate.innerHTML = this.getComponent('pl-error-template').innerHTML;
            errorTemplate.firstElementChild.innerHTML = message;
            input.after(errorTemplate.firstElementChild);
            input.parentElement.classList.add('pl-error');
        };

        /**
         * Removes error from input form group element.
         *
         * @param {Element} input
         */
        this.removeError = function (input) {
            let errorElement = input.parentNode.querySelector('.pl-error-message');
            if (errorElement) {
                input.parentNode.removeChild(errorElement);
            }

            input.parentElement.classList.remove('pl-error');
        };
    }

    Packlink.templateService = new TemplateService();
})();