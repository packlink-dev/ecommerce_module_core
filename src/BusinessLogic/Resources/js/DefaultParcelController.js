var Packlink = window.Packlink || {};

(function () {
    function DefaultParcelController(configuration) {
        const defaultParcelFields = [
            'weight',
            'width',
            'length',
            'height',
        ];

        const numericInputs = [
            'weight',
            'width',
            'length',
            'height',
        ];

        const integers = [
            'width',
            'length',
            'height',
        ];

        let templateService = Packlink.templateService;
        let utilityService = Packlink.utilityService;
        let ajaxService = Packlink.ajaxService;
        let state = Packlink.state;

        let parcelData;
        let page;

        //Register public methods and variables.
        this.display = display;

        /**
         * Displays page content.
         */
        function display() {
            utilityService.showSpinner();
            ajaxService.get(configuration.getUrl, constructPage);
        }

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
                let input = templateService.getComponent(`pl-default-parcel-${field}`, page);
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
            let value = event.target.value;
            let field = event.target.id.substr('pl-default-parcel-'.length);

            if (value === '') {
                templateService.setError(event.target, Packlink.errorMsgs.required);
            } else if (numericInputs.indexOf(field) !== -1) {
                let numericValue = parseFloat(value);
                if (value == numericValue) {
                    if (numericValue <= 0) {
                        templateService.setError(event.target, Packlink.errorMsgs.greaterThanZero);
                    } else if (integers.indexOf(field) !== -1 && numericValue != parseInt(value)) {
                        templateService.setError(event.target, Packlink.errorMsgs.integer);
                    } else {
                        templateService.removeError(event.target);
                    }
                } else {
                    templateService.setError(event.target, Packlink.errorMsgs.numeric);
                }
            } else {
                templateService.removeError(event.target);
            }
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
                        templateService.getComponent(`pl-default-parcel-${field}`, page)
                    );
                }
            }

            if (isValid) {
                utilityService.showSpinner();
                ajaxService.post(
                    configuration.submitUrl,
                    model,
                    function (response) {
                        utilityService.hideSpinner();

                        if (configuration.fromStep) {
                            state.stepFinished();
                        }
                    },
                    function (response) {
                        for (let field in response) {
                            if (response.hasOwnProperty(field)) {
                                let input = templateService.getComponent(`pl-default-parcel-${field}`, page);
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
                let value = getInputValue(`pl-default-parcel-${field}`);
                let isValid = true;

                if (value === '') {
                    isValid = false;
                    templateService.setError(
                        templateService.getComponent(`pl-default-parcel-${field}`, page),
                        Packlink.errorMsgs.required
                    );
                } else if (numericInputs.indexOf(field) !== -1) {
                    let numericValue = parseFloat(value);
                    if (value == numericValue) {
                        if (numericValue <= 0) {
                            isValid = false;
                            templateService.setError(
                                templateService.getComponent(`pl-default-parcel-${field}`, page),
                                Packlink.errorMsgs.greaterThanZero
                            );
                        } else if (integers.indexOf(field) !== -1 && numericValue != parseInt(value)) {
                            isValid = false;
                            templateService.setError(
                                templateService.getComponent(`pl-default-parcel-${field}`, page),
                                Packlink.errorMsgs.integer
                            );
                        } else {
                            templateService.removeError(templateService.getComponent(`pl-default-parcel-${field}`, page));
                        }
                    } else {
                        isValid = false;
                        templateService.setError(
                            templateService.getComponent(`pl-default-parcel-${field}`, page),
                            Packlink.errorMsgs.numeric
                        );
                    }
                } else {
                    templateService.removeError(templateService.getComponent(`pl-default-parcel-${field}`, page));
                }


                model[field] = isValid ? value : null;
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
    }

    Packlink.DefaultParcelController = DefaultParcelController;
})();