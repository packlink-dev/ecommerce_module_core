var Packlink = window.Packlink || {};

(function () {
    /**
     * Handles login page logic.
     *
     * @param {{submit: string}} configuration
     * @constructor
     */
    function LoginController(configuration) {

        const templateService = Packlink.templateService,
            ajaxService = Packlink.ajaxService,
            state = Packlink.state,
            templateId = 'pl-login-page';
        let errorMsg, loginBtn;

        /**
         * Displays page content.
         */
        this.display = function () {
            templateService.setCurrentTemplate(templateId);

            const loginPage = templateService.getMainPage();

            loginBtn = templateService.getComponent('pl-login-button');
            errorMsg = templateService.getComponent('pl-login-error-msg');

            templateService.getComponent('pl-login-form', loginPage).addEventListener('submit', login);
            const input = templateService.getComponent('pl-login-api-key', loginPage);
            input.addEventListener('input', enableButton);
        };

        /**
         * Handles form submit.
         * @param event
         * @returns {boolean}
         */
        function login(event) {
            event.preventDefault();

            errorMsg.classList.add('pl-hidden');
            ajaxService.post(configuration.submit, {apiKey: event.target['apiKey'].value}, successfulLogin, failedLogin);

            return false;
        }

        function enableButton(event) {
            loginBtn.disabled = event.target.value.length === 0;
        }

        function successfulLogin(response) {
            if (response.success) {
                errorMsg.classList.add('pl-hidden');
                state.goToState('onboarding');
            } else {
                errorMsg.classList.remove('pl-hidden');
            }
        }

        function failedLogin() {
            errorMsg.classList.remove('pl-hidden');
        }
    }

    Packlink.LoginController = LoginController;
})();
