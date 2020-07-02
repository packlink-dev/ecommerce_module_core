if (!window.Packlink) {
    window.Packlink = {};
}

(function () {
    function TemplateService() {
        /**
         * The configuration object for all templates.
         */
        let templates = {};
        let mainPlaceholder = '#pl-main-page-holder';

        this.setMainPlaceholder = placeholder => {
            mainPlaceholder = placeholder;
        };

        /**
         * Gets the main page DOM element.
         *
         * @returns {Element}
         */
        this.getMainPage = () => document.querySelector(mainPlaceholder);

        /**
         * Retrieves component by its id or attribute.
         *
         * @param {string} component
         * @param {Element} [element]
         * @param {string|int} [attribute]
         *
         * @return {Element}
         */
        this.getComponent = (component, element, attribute) => {
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
        this.getComponentsByAttribute = (attribute, element) => {
            let selector = '[' + attribute + ']';

            if (!element) {
                return document.querySelectorAll(selector);
            }

            return element.querySelectorAll(selector);
        };

        /**
         * Sets the content templates.
         *
         * @param {{}} configuration
         */
        this.setTemplates = configuration => {
            templates = configuration;
        };

        /**
         * Gets the template with translated text.
         *
         * @param {string} templateId
         *
         * @return {string} HTML as string.
         */
        this.getTemplate = templateId => Packlink.translationService.translateHtml(templates[templateId]);

        /**
         * Sets current template in the page.
         *
         * @param {string} templateId
         */
        this.setCurrentTemplate = templateId => {
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
        this.clearComponent = component => {
            while (component.firstChild) {
                component.removeChild(component.firstChild);
            }
        };
    }

    Packlink.templateService = new TemplateService();
})();