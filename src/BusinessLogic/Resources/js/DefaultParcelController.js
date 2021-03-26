if (!window.Packlink) {
    window.Packlink = {};
}

(function () {
    /**
     * @typedef {{weight: number, length: number, width: number, height: number}} Parcel
     */

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

        this.modelFields = [
            'weight',
            'width',
            'length',
            'height'
        ];

        this.config = {};
        this.pageId = 'pl-default-parcel-page';
        this.pageKey = 'defaultParcel';

        /**
         * Handles Back button navigation.
         */
        const goToPreviousPage = () => {
            state.goToState(this.config.prevState);
        };

        /**
         * Handles Save button navigation.
         */
        const goToNextPage = () => {
            state.goToState(this.config.nextState, {
                'code': this.config.code,
                'prevState': this.config.prevState,
                'nextState': 'onboarding-overview',
            });
        };

        /**
         * Displays page content.
         *
         * @param {{code:string, prevState: string, nextState: string}} displayConfig
         */
        this.display = (displayConfig) => {
            this.config = displayConfig;
            ajaxService.get(configuration.getUrl, this.constructPage);
        };

        /**
         * Constructs default parcel page.
         *
         * @param {Parcel} response
         */
        this.constructPage = (response) => {
            templateService.setCurrentTemplate(this.pageId);

            const form = templateService.getMainPage().querySelector('form');
            validationService.setFormValidation(form, this.modelFields);

            for (let field of this.modelFields) {
                if (response[field]) {
                    form[field].value = response[field];
                }
            }

            const submitButton = templateService.getComponent('pl-page-submit-btn');
            submitButton.addEventListener('click', submitPage, true);

            setTemplateBasedOnState();

            utilityService.hideSpinner();
        };

        const setTemplateBasedOnState = () => {
            let mainPage = templateService.getMainPage(),
                page = mainPage.querySelector('.' + this.pageId),
                backButton = mainPage.querySelector('.pl-sub-header button'),
                headerEl = mainPage.querySelector('.pl-sub-header h1'),
                pageDescription = mainPage.querySelector('p.pl-page-info'),
                submitButton = mainPage.querySelector('.pl-page-buttons button');

            page.classList.add('pl-page-' + this.config.code);
            backButton.addEventListener('click', goToPreviousPage);
            headerEl.innerHTML = translationService.translate(this.pageKey + '.title-' + this.config.code);
            pageDescription.innerHTML = translationService.translate(this.pageKey + '.description-' + this.config.code);

            if (state.getPreviousState() === 'onboarding-welcome') {
                submitButton.innerText = translationService.translate('general.continue');
            } else if (state.getPreviousState() === 'onboarding-overview') {
                submitButton.innerText = translationService.translate('general.save');
            } else {
                submitButton.innerText = translationService.translate('general.saveChanges');
            }
        };

        /**
         * Submits the form.
         */
        const submitPage = () => {
            const form = templateService.getMainPage().querySelector('form');

            if (!validationService.validateForm(form)) {
                return false;
            }

            utilityService.showSpinner();
            ajaxService.post(
                configuration.submitUrl,
                this.getFormFields(form),
                goToNextPage,
                Packlink.responseService.errorHandler
            );
        };

        /**
         * Gets the form field values model.
         *
         * @param {HTMLElement} form
         * @return {{}}
         */
        this.getFormFields = (form) => {
            let model = {};

            for (let field of this.modelFields) {
                // + is to convert a string to a number
                model[field] = form[field].value !== '' ? +form[field].value : null;
            }

            return model;
        };
    }

    Packlink.DefaultParcelController = DefaultParcelController;
})();