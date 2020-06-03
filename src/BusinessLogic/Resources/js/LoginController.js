var Packlink = window.Packlink || {};

(function () {
    /**
     * Handles login page logic.
     *
     * @param configuration
     * @constructor
     */
    function LoginController(configuration) {

        let templateService = Packlink.templateService,
            ajaxService = Packlink.ajaxService,
            state = Packlink.state,
            templateId = 'pl-login-page';

        /**
         * Displays page content.
         */
        this.display = function () {
            templateService.setCurrentTemplate(templateId);

            let loginPage = templateService.getComponent('pl-login-page');

            templateService.getComponent('pl-login-form', loginPage).addEventListener(
                'submit',
                login
            );
        };

        /**
         * Handles form submit.
         * @param event
         * @returns {boolean}
         */
        function login(event) {
            event.preventDefault();

            ajaxService.post(configuration.submit, {apiKey: event.target['apiKey'].value}, successfulLogin, failedLogin);

            return false;
        }

        function successfulLogin(response) {
            let errorMsg = templateService.getComponent('pl-login-error-msg');

            if (response.success) {
                errorMsg.classList.remove('visible');
                state.goToState('onboarding');
            } else {
                errorMsg.classList.add('visible');
            }
        }

        function failedLogin() {
            console.log('Unhandled error!');
        }
    }

    Packlink.LoginController = LoginController;
})();
