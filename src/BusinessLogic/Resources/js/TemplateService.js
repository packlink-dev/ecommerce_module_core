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
        this.baseResourceUrl = '';

        /**
         * Sets the base resource URL.
         *
         * @param {string} url
         */
        this.setBaseResourceUrl = (url) => {
            this.baseResourceUrl = url;
        };

        /**
         * Sets the main page placeholder. If not set, defaults to the one set in this service.
         *
         * @param {string} placeholder
         */
        this.setMainPlaceholder = (placeholder) => {
            mainPlaceholder = placeholder;
        };

        /**
         * Gets the main page DOM element.
         *
         * @returns {Element}
         */
        this.getMainPage = () => document.querySelector(mainPlaceholder);

        /**
         * Gets the header of the page.
         *
         * @return {HTMLElement}
         */
        this.getHeader = () => document.getElementById('pl-main-header');

        /**
         * Retrieves component by its id or attribute.
         *
         * @param {string} component
         * @param {Element} [element]
         * @param {string|int} [attribute]
         *
         * @return {HTMLElement}
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
         * Sets the content templates.
         *
         * @param {{}} configuration
         */
        this.setTemplates = (configuration) => {
            templates = configuration;
        };

        /**
         * Gets the template with translated text.
         *
         * @param {string} templateId
         *
         * @return {string} HTML as string.
         */
        this.getTemplate = (templateId) => this.replaceResourcesUrl(
            Packlink.translationService.translateHtml(templates[templateId])
        );

        /**
         * Sets current template in the page.
         *
         * @param {string} templateId
         */
        this.setCurrentTemplate = (templateId) => {
            for (let [extensionPointId, html] of Object.entries(templates[templateId])) {
                const component = this.getComponent(extensionPointId);

                if (component) {
                    component.innerHTML = this.replaceResourcesUrl(html ? Packlink.translationService.translateHtml(html) : '');
                }
            }
        };

        /**
         * Replaces all resources URL placeholders with the correct URL.
         *
         * @param {string} html
         * @return {string}
         */
        this.replaceResourcesUrl = (html) => html.replace(/{\$BASE_URL\$}/g, this.baseResourceUrl);

        /**
         * Removes component's children.
         *
         * @param {Element} component
         */
        this.clearComponent = (component) => {
            while (component.firstChild) {
                component.removeChild(component.firstChild);
            }
        };
    }

    Packlink.templateService = new TemplateService();
})();