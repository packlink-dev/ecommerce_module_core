if (!window.Packlink) {
    window.Packlink = {};
}

(function () {
    /**
     * Handles System info dialog.
     *
     * @constructor
     *
     * @param {{getStatusUrl: string, setStatusUrl: string}} config
     */
    function SystemInfoController(config) {
        // noinspection JSCheckFunctionSignatures
        const ajaxService = Packlink.ajaxService,
            utilityService = Packlink.utilityService,
            modal = new Packlink.modalService({
                title: Packlink.translationService.translate('systemInfo.title'),
                content: Packlink.templateService.getTemplate('pl-system-info-modal'),
                onOpen: (modal) => {
                    ajaxService.get(config.getStatusUrl, (response) => {
                        getStatus(modal, response);
                    });
                }
            });

        /**
         * @param {HTMLDivElement} modal
         * @param {{status: boolean, downloadUrl: string}} response
         */
        const getStatus = (modal, response) => {
            const checkbox = modal.querySelector('#pl-debug-status'),
                buttonLink = modal.querySelector('a');

            checkbox.checked = response.status;
            checkbox.addEventListener('click', () => {
                ajaxService.post(config.setStatusUrl, {'status': checkbox.checked});
            });

            buttonLink.href = response.downloadUrl;

            utilityService.hideSpinner();
        };

        /**
         * Displays page content.
         */
        this.display = () => {
            modal.open();
        };
    }

    Packlink.SystemInfoController = SystemInfoController;
})();
