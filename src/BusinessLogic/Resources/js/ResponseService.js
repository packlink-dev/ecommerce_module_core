if (!window.Packlink) {
    window.Packlink = {};
}

(function () {
    /**
     * The ResponseService constructor.
     *
     * @constructor
     */
    function ResponseService() {
        const utilityService = Packlink.utilityService,
            validationService = Packlink.validationService;

        /**
         * Handles an error response from the submit action.
         *
         * @param {{success: boolean, error?: string, messages?: ValidationMessage[]}} response
         */
        this.errorHandler = (response) => {
            utilityService.hideSpinner();
            if (response.error) {
                utilityService.showFlashMessage(response.error, 'danger', 7000);
            } else if (response.messages) {
                validationService.handleValidationErrors(response.messages);
            } else {
                utilityService.showFlashMessage('Unknown error occurred.', 'danger', 7000);
            }
        };
    }

    Packlink.responseService = new ResponseService();
})();
