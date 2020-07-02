var Packlink = window.Packlink || {};

(function () {
    function DefaultParcelController(configuration) {
        const defaultParcelFields = [
            'weight',
            'width',
            'length',
            'height'
        ];

        const numericInputs = [
            'weight',
            'width',
            'length',
            'height'
        ];

        const integers = [
            'width',
            'length',
            'height'
        ];

        const ONBOARDING_OVERVIEW_STATE = 'onboarding-overview';
        const ONBOARDING_WELCOME_STATE = 'onboarding-welcome';
        const ONBOARDING_WH_STATE = 'onboarding-warehouse';
        // TODO: Add when configuration is done.
        const CONFIGURATION_STATE = '';

        let templateService = Packlink.templateService;
        let utilityService = Packlink.utilityService;
        let ajaxService = Packlink.ajaxService;
        let state = Packlink.state;
        let translationService = Packlink.translationService;

        let parcelData;
        let page;

        /**
         * Displays page content.
         */
        this.display = function () {
            utilityService.showSpinner();
            ajaxService.get(configuration.getUrl, constructPage);
        };

        /**
         * Constructs default parcel page by filling form fields
         * with existing data and also adds event handler to submit button.
         *
         * @param {object} response
         */
        function constructPage(response) {
            parcelData = response;
            page = templateService.setCurrentTemplate('pl-default-parcel-page');

            for (let field of defaultParcelFields) {
                let input = templateService.getComponent('pl-default-parcel-' + field, page);
                input.addEventListener('blur', onBlurHandler, true);
                input.addEventListener('input', onInputHandler, true);

                if (parcelData[field]) {
                    input.value = parcelData[field];
                }
            }

            let submitButton = templateService.getComponent(
                'pl-default-parcel-submit-btn',
                page
            );

            if (isFormValid()) {
                submitButton.disabled = false;
            }

            setTemplateBasedOnState();

            submitButton.addEventListener('click', handleDefaultParcelSubmitButtonClickedEvent, true);

            utilityService.hideSpinner();
        }

        function setTemplateBasedOnState() {
            let backButton = templateService.getComponent('pl-parcel-back'),
                submitButton = templateService.getComponent('pl-default-parcel-submit-btn'),
                headerEl = document.querySelector('.pl-default-parcel-page .pl-text-center span'),
                headerInfoEl = document.querySelector('.pl-default-parcel-page .pl-header-info');

            if (state.getPreviousState() === ONBOARDING_WELCOME_STATE) {
                setOnboardingCommon('defaultParcel.continue');
            } else if (state.getPreviousState() === ONBOARDING_OVERVIEW_STATE) {
                setOnboardingCommon('defaultParcel.save');
            } else {
                page.classList.add('pl-form-left-align');
            }

            function setOnboardingCommon(btnLabel) {
                submitButton.innerText = translationService.translate(btnLabel);
                backButton.addEventListener('click', goToOverviewPage);
                headerEl.innerHTML += '1. ' + translationService.translate('onboardingParcel.header');
                headerInfoEl.innerHTML += translationService.translate('onboardingParcel.info');
            }
        }

        function goToOverviewPage() {
            state.goToState(ONBOARDING_OVERVIEW_STATE);
        }

        /**
         * Handles on blur action.
         *
         * @param event
         */
        function onBlurHandler(event) {
            validateField(event.target.id.substr('pl-default-parcel-'.length), event.target.value, event.target);

            let submitButton = templateService.getComponent(
                'pl-default-parcel-submit-btn',
                page
            )

            submitButton.disabled = !isFormValid();
        }

        /**
         * Handles on blur action.
         *
         * @param event
         */
        function onInputHandler(event) {
            templateService.removeError(event.target);
        }

        /**
         * Submits default parcel form.
         *
         * @param event
         */
        function handleDefaultParcelSubmitButtonClickedEvent(event) {
            let model = getFormattedParcelFormInput(true);

            let isValid = true;
            for (let field of defaultParcelFields) {
                if (!model[field]) {
                    isValid = false;
                } else {
                    templateService.removeError(
                        templateService.getComponent('pl-default-parcel-' + field, page)
                    );
                }
            }

            if (isValid) {
                utilityService.showSpinner();
                ajaxService.post(
                    configuration.submitUrl,
                    model,
                    function (event) {
                        event.preventDefault();
                        utilityService.hideSpinner();

                        if (state.getPreviousState() === ONBOARDING_WELCOME_STATE) {
                            state.goToState(ONBOARDING_WH_STATE);
                        } else if (state.getPreviousState() === ONBOARDING_OVERVIEW_STATE) {
                            state.goToState(ONBOARDING_OVERVIEW_STATE);
                        }

                        return false;
                    },
                    function (response) {
                        for (let field in response) {
                            if (response.hasOwnProperty(field)) {
                                let input = templateService.getComponent('pl-default-parcel-' + field, page);
                                if (input) {
                                    templateService.setError(input, response[field]);
                                }
                            }
                        }

                        utilityService.hideSpinner();
                    });
            }
        }

        /**
         * Retrieves formatted input from default parcel form.
         *
         * @param validateWithDisplay boolean
         *
         * @returns {object}
         */
        function getFormattedParcelFormInput(validateWithDisplay) {
            let model = {};

            for (let field of defaultParcelFields) {
                let value = getInputValue('pl-default-parcel-' + field),
                    element = templateService.getComponent('pl-default-parcel-' + field, page),
                    error = validateWithDisplay ? validateField(field, value, element) : validateFieldValue(field, value);

                model[field] = error ? null : value;
            }

            return model;
        }

        /**
         * Retrieves input field's value.
         *
         * @param input
         * @return {string}
         */
        function getInputValue(input) {
            return templateService.getComponent(input, page).value;
        }

        /**
         * Validates if the field value is correct and displays the error on element.
         *
         * @param {string} field The name of the field to validate.
         * @param {string} value The value to validate.
         * @param {Element} element The element to display error on.
         * @returns {string}
         */
        function validateField(field, value, element) {
            let error = validateFieldValue(field, value)

            if (error) {
                templateService.setError(element, error);
            } else {
                templateService.removeError(element);
            }

            return error;
        }

        function validateFieldValue(field, value) {
            let error = '';

            if (value === '') {
                error = Packlink.errorMsgs.required;
            } else if (numericInputs.indexOf(field) !== -1) {
                let numericValue = parseFloat(value);
                // noinspection EqualityComparisonWithCoercionJS Because it checks parsing.
                if (value == numericValue) {
                    if (numericValue <= 0) {
                        error = Packlink.errorMsgs.greaterThanZero;
                    } else {
                        // noinspection EqualityComparisonWithCoercionJS Cannot compare float and int with !==.
                        if (integers.indexOf(field) !== -1 && numericValue != parseInt(value)) {
                            error = Packlink.errorMsgs.integer;
                        }
                    }
                } else {
                    error = Packlink.errorMsgs.numeric;
                }
            }

            return error;
        }

        function isFormValid() {
            let model = getFormattedParcelFormInput(false);

            let isValid = true;
            for (let field of defaultParcelFields) {
                if (!model[field]) {
                    isValid = false;
                    break;
                }
            }

            return isValid;
        }
    }

    Packlink.DefaultParcelController = DefaultParcelController;
})();