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

        let templateService = Packlink.templateService;
        let utilityService = Packlink.utilityService;
        let ajaxService = Packlink.ajaxService;
        let state = Packlink.state;

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
            page = templateService.setTemplate('pl-default-parcel-template');

            for (let field of defaultParcelFields) {
                let input = templateService.getComponent('pl-default-parcel-' + field, page);
                input.addEventListener('blur', onBlurHandler, true);
                if (parcelData[field]) {
                    input.value = parcelData[field];
                }
            }

            let submitButton = templateService.getComponent(
                'pl-default-parcel-submit-btn',
                page
            );

            submitButton.addEventListener('click', handleDefaultParcelSubmitButtonClickedEvent, true);

            utilityService.configureInputElements();
            utilityService.hideSpinner();
        }

        /**
         * Handles on blur action.
         *
         * @param event
         */
        function onBlurHandler(event) {
            validateField(event.target.id.substr('pl-default-parcel-'.length), event.target.value, event.target);
        }

        /**
         * Submits default parcel form.
         *
         * @param event
         */
        function handleDefaultParcelSubmitButtonClickedEvent(event) {
            let model = getFormattedParcelFormInput();

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
                    function () {
                        utilityService.hideSpinner();

                        if (configuration.fromStep) {
                            state.stepFinished();
                        }
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
         * @return {object}
         */
        function getFormattedParcelFormInput() {
            let model = {};

            for (let field of defaultParcelFields) {
                let value = getInputValue('pl-default-parcel-' + field),
                    element = templateService.getComponent('pl-default-parcel-' + field, page),
                    error = validateField(field, value, element);

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

            if (error) {
                templateService.setError(element, error);
            } else {
                templateService.removeError(element);
            }

            return error;
        }
    }

    Packlink.DefaultParcelController = DefaultParcelController;
})();