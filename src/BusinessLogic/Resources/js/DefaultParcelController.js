if (!window.Packlink) {
    window.Packlink = {};
}

(function () {
    /**
     * @param {{getUrl: string, submitUrl: string}} configuration
     * @constructor
     */
    function DefaultParcelController(configuration) {
        const templateService = Packlink.templateService,
            utilityService = Packlink.utilityService,
            validationService = Packlink.validationService,
            ajaxService = Packlink.ajaxService,
            state = Packlink.state,
            translationService = Packlink.translationService;

        const defaultParcelFields = [
            'weight',
            'width',
            'length',
            'height'
        ];

        let config;

        /**
         * Handles Back button navigation.
         */
        const goToPreviousPage = () => {
            state.goToState(config.prevState);
        };

        /**
         * Handles Save button navigation.
         */
        const goToNextPage = () => {
            state.goToState(config.nextState);
        };

        /**
         * Displays page content.
         *
         * @param {{code:string, prevState: string, nextState: string}} displayConfig
         */
        this.display = (displayConfig) => {
            utilityService.showSpinner();
            config = displayConfig;
            ajaxService.get(configuration.getUrl, constructPage);
        };

        /**
         * Constructs default parcel page by filling form fields
         * with existing data and also adds event handler to submit button.
         *
         * @param {object} response
         */
        const constructPage = response => {
            const page = templateService.getMainPage();
            templateService.setCurrentTemplate('pl-default-parcel-page');

            for (let field of defaultParcelFields) {
                let input = templateService.getComponent('pl-default-parcel-' + field, page);
                input.addEventListener('blur', onBlurHandler, true);
                input.addEventListener('input', onInputHandler, true);

                if (response[field]) {
                    input.value = response[field];
                }
            }

            const submitButton = templateService.getComponent('pl-default-parcel-submit-btn');
            submitButton.addEventListener('click', submitPage, true);

            setTemplateBasedOnState();

            utilityService.hideSpinner();
        };

        const setTemplateBasedOnState = () => {
            let mainPage = templateService.getMainPage(),
                page = mainPage.querySelector('.pl-default-parcel-page'),
                backButton = mainPage.querySelector('.pl-sub-header button'),
                headerEl = mainPage.querySelector('.pl-sub-header h1'),
                pageDescription = mainPage.querySelector('p.pl-page-info'),
                submitButton = mainPage.querySelector('.pl-page-buttons button');

            page.classList.add('pl-page-' + config.code);
            backButton.addEventListener('click', goToPreviousPage);
            headerEl.innerHTML = translationService.translate('defaultParcel.title-' + config.code);
            pageDescription.innerHTML = translationService.translate('defaultParcel.description-' + config.code);

            if (state.getPreviousState() === 'onboarding-welcome') {
                submitButton.innerText = translationService.translate('general.continue');
            } else if (state.getPreviousState() === 'onboarding-overview') {
                submitButton.innerText = translationService.translate('general.save');
            } else {
                submitButton.innerText = translationService.translate('general.saveChanges');
            }
        };

        /**
         * Handles on blur action.
         *
         * @param event
         */
        const onBlurHandler = event => {
            validationService.validateInputField(event.target);
        };

        /**
         * Handles on blur action.
         *
         * @param event
         */
        const onInputHandler = event => {
            validationService.removeError(event.target);
        };

        /**
         * Submits default parcel form.
         */
        const submitPage = (event) => {
            event.preventDefault();

            const form = templateService.getComponent('pl-parcel-form');

            if (!validationService.validateForm(form)) {
                return false;
            }

            let model = {};

            for (let field of defaultParcelFields) {
                // + is to convert a string to a number
                model[field] = form[field].value !== '' ? +form[field].value : null;
            }

            utilityService.showSpinner();
            ajaxService.post(
                configuration.submitUrl,
                model,
                goToNextPage,
                Packlink.responseService.errorHandler
            );
        };

    }

    Packlink.DefaultParcelController = DefaultParcelController;
})();