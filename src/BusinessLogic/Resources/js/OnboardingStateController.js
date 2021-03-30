var Packlink = window.Packlink || {};

(function () {
    function OnboardingStateController(configuration) {

        const state = Packlink.state,
            ajaxService = Packlink.ajaxService,
            welcomeController = 'onboarding-welcome',
            overviewController = 'onboarding-overview';

        /**
         * Displays page content.
         */
        this.display = function () {
            ajaxService.get(configuration.getState, showPageBasedOnState);
        };

        function showPageBasedOnState(response) {
            if (response.state === 'welcome') {
                state.goToState(welcomeController);
            } else {
                state.goToState(overviewController);
            }
        }
    }

    Packlink.OnboardingStateController = OnboardingStateController;
})();
