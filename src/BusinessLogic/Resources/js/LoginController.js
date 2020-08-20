if (!window.Packlink) {
    window.Packlink = {};
}

(function () {
    /**
     * Handles login page logic.
     *
     * @param {{submit: string, listOfCountriesUrl: string}} configuration
     * @constructor
     */
    function LoginController(configuration) {

        const templateService = Packlink.templateService,
            ajaxService = Packlink.ajaxService,
            state = Packlink.state,
            templateId = 'pl-login-page',
            errorMessage = Packlink.translationService.translate('login.apiKeyIncorrect');

        let inputElem, loginBtn;

        /**
         * Displays page content.
         */
        this.display = () => {
            templateService.setCurrentTemplate(templateId);

            const loginPage = templateService.getMainPage();

            loginBtn = templateService.getComponent('pl-login-button');
            inputElem = templateService.getComponent('pl-login-api-key');
            inputElem.addEventListener('input', (event) => {
                enableButton(event);
            });

            templateService.getComponent('pl-login-form', loginPage).addEventListener('submit', login);
            templateService.getComponent('pl-go-to-register', loginPage).addEventListener('click', goToRegister);
            Packlink.utilityService.hideSpinner();
        };

        /**
         * Handles form submit.
         * @param event
         * @returns {boolean}
         */
        const login = (event) => {
            event.preventDefault();

            Packlink.utilityService.showSpinner();

            ajaxService.post(configuration.submit, {apiKey: event.target['apiKey'].value}, successfulLogin, failedLogin);

            return false;
        };

        /**
         * Redirects to register.
         *
         * @param event
         *
         * @returns {boolean}
         */
        const goToRegister = (event) => {
            event.preventDefault();

            let registerModalController = new Packlink.RegisterModalController(
                'pl-modal-mask',
                configuration.listOfCountriesUrl,
            );
            registerModalController.display();

            return false;
        };

        const enableButton = (event) => {
            Packlink.validationService.removeError(inputElem);
            loginBtn.disabled = event.target.value.length === 0;
        };

        const successfulLogin = (response) => {
            if (response.success) {
                state.goToState('onboarding-state');
            } else {
                failedLogin();
            }
        };

        const failedLogin = () => {
            Packlink.validationService.setError(inputElem, errorMessage);
            Packlink.utilityService.hideSpinner();
        };
    }

    Packlink.LoginController = LoginController;
})();
