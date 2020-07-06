if (!window.Packlink) {
    window.Packlink = {};
}

(function () {
    function OnboardingWelcomeController() {

        const templateService = Packlink.templateService,
            templateId = 'pl-onboarding-welcome-page';

        /**
         * Displays page content.
         */
        this.display = () => {
            templateService.setCurrentTemplate(templateId);

            const btn = templateService.getComponent('pl-onboarding-welcome-button');
            btn.addEventListener('click', () => {
                Packlink.state.goToState('default-parcel');
            });
        };
    }

    Packlink.OnboardingWelcomeController = OnboardingWelcomeController;
})();
