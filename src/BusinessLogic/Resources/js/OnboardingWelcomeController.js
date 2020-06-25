var Packlink = window.Packlink || {};

(function () {
    function OnboardingWelcomeController() {

        const templateService = Packlink.templateService,
            templateId = 'pl-onboarding-welcome-page';

        /**
         * Displays page content.
         */
        this.display = function () {
            templateService.setCurrentTemplate(templateId);
        };
    }

    Packlink.OnboardingWelcomeController = OnboardingWelcomeController;
})();
