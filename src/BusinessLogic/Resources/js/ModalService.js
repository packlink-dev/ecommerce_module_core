if (!window.Packlink) {
    window.Packlink = {};
}

(function () {
    /**
     * @typedef ButtonConfig
     * @property {string} title
     * @property {string[]} [cssClass]
     * @property {function()} onClick
     */

    /**
     * @typedef ModalConfiguration
     * @property {string} [title]
     * @property {string} [content] The content of the body.
     * @property {ButtonConfig[]} [buttons] Footer buttons, if any. If not provided, the footer will not be displayed.
     * @property {function(HTMLDivElement)} [onOpen] Will fire after the modal is opened.
     * @property {function():boolean} [onClose] Will fire before the modal is closed.
     *      If the return value is false, the modal will not be closed.
     * @property {boolean} [footer=false] Indicates whether to use footer. Defaults to false.
     * @property {boolean} [useX=true] Indicates whether to use the X close button. Defaults to true.
     */

    /**
     * @param {ModalConfiguration} configuration
     * @constructor
     */
    function ModalService(configuration) {
        const modalId = 'pl-modal-mask',
            templateService = Packlink.templateService,
            utilityService = Packlink.utilityService,
            config = configuration;
        /**
         * @type {HTMLDivElement}
         */
        let modal;

        /**
         * Creates a footer button.
         *
         * @param {ButtonConfig} button
         *
         * @return {HTMLButtonElement}
         */
        const createButton = button => {
            const buttonElem = document.createElement('button');
            const cssClasses = button.cssClass ? ['pl-button', ...button.cssClass] : ['pl-button'];

            buttonElem.classList.add(cssClasses.join(' '));
            buttonElem.addEventListener('click', button.onClick);
            buttonElem.innerHTML = button.title;

            return buttonElem;
        };

        /**
         * Closes the modal.
         */
        this.close = () => {
            if (!config.onClose || config.onClose()) {
                modal.remove();
            }
        };

        /**
         * Opens the modal.
         */
        this.open = () => {
            // noinspection JSValidateTypes
            modal = templateService.getComponent(modalId).cloneNode(true);
            const closeBtn = modal.querySelector('.pl-modal-close-button'),
                title = modal.querySelector('.pl-modal-title'),
                body = modal.querySelector('.pl-modal-body'),
                footer = modal.querySelector('.pl-modal-footer');

            utilityService.showElement(modal);
            if (config.useX === false) {
                utilityService.hideElement(closeBtn);
            } else {
                closeBtn.addEventListener('click', this.close);
            }

            if (config.title) {
                title.innerHTML = config.title;
            } else {
                utilityService.hideElement(title);
            }

            body.innerHTML = config.content;

            if (config.footer === false || !config.buttons) {
                utilityService.hideElement(footer);
            } else {
                config.buttons.forEach(button => {
                    const buttonElem = createButton(button);
                    footer.appendChild(buttonElem);
                });
            }

            templateService.getMainPage().parentNode.appendChild(modal);
            if (config.onOpen) {
                config.onOpen(modal);
            }
        };
    }

    Packlink.modalService = ModalService;
})();