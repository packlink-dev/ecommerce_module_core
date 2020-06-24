var Packlink = window.Packlink || {};

(function () {
    function OnboardingStateController(configuration) {

        const templateService = Packlink.templateService,
            ajaxService = Packlink.ajaxService,
            welcomeTemplateId = 'pl-onboarding-welcome-page',
            overviewTemplateId = 'pl-onboarding-overview-page';

        /**
         * Displays page content.
         */
        this.display = function () {
            ajaxService.get(configuration.getState, showPageBasedOnState);
        };

        function showPageBasedOnState(response) {
            if (response.state === 'welcome') {
                templateService.setCurrentTemplate(welcomeTemplateId);
            } else {
                templateService.setCurrentTemplate(overviewTemplateId);
            }
        }
    }

    Packlink.OnboardingStateController = OnboardingStateController;
})();
