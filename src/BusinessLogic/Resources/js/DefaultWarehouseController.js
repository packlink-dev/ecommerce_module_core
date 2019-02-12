var Packlink = window.Packlink || {};

(function () {
    function DefaultWarehouseController(configuration) {
        const warehouseFields = [
            'alias',
            'name',
            'surname',
            'company',
            'postal_code',
            'address',
            'phone',
            'email'
        ];

        const requiredFields = [
            'alias',
            'name',
            'surname',
            'postal_code',
            'address',
            'phone',
            'email'
        ];

        let templateService = Packlink.templateService;
        let utilityService = Packlink.utilityService;
        let ajaxService = Packlink.ajaxService;
        let state = Packlink.state;
        let page;

        let country;

        //Register public methods and variables.
        this.display = display;

        /**
         * Displays page content.
         */
        function display() {
            page = templateService.setTemplate('pl-default-warehouse-template');
            utilityService.showSpinner();
            ajaxService.get(configuration.getUrl, constructPage);
        }

        /**
         * Attaches event handler to submit button.
         * Fills form with existing warehouse data retrieved from server.
         *
         * @param response
         */
        function constructPage(response) {
            country = response['country'];

            for (let field of warehouseFields) {
                let input = templateService.getComponent(`pl-default-warehouse-${field}`, page);
                input.addEventListener('blur', onBlurHandler, true);
                if (response[field]) {
                    input.value = response[field];
                }
            }

            let submitButton = templateService.getComponent(
                'pl-default-warehouse-submit-btn',
                page
            );

            submitButton.addEventListener('click', handleSubmitButtonClicked, true);
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
            let field = event.target.getAttribute('id').split('-')[3];

            if (!value && requiredFields.indexOf(field) !== -1) {
                templateService.setError(event.target, Packlink.errorMsgs.required);
            } else {
                if (field === 'phone') {
                    if (!isPhoneValid(value)) {
                        templateService.setError(event.target, Packlink.errorMsgs.phone);
                    } else {
                        templateService.removeError(event.target);
                    }
                } else {
                    templateService.removeError(event.target);
                }
            }
        }

        /**
         * Handles event when submit button is clicked.
         */
        function handleSubmitButtonClicked() {
            let model = getFormattedWarehouseInput();
            let isValid = true;

            for (let field of warehouseFields) {
                if (model[field] === null) {
                    templateService.setError(
                        templateService.getComponent(`pl-default-warehouse-${field}`, page),
                        Packlink.errorMsgs.required
                    );
                    isValid = false;
                } else {
                    templateService.removeError(templateService.getComponent(`pl-default-warehouse-${field}`, page));
                }
            }

            if (isValid) {
                utilityService.showSpinner();
                model['country'] = country;
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
                                let input = templateService.getComponent(`pl-default-warehouse-${field}`, page);
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
         * Retrieves formatted input from default warehouse form.
         *
         * @return {object}
         */
        function getFormattedWarehouseInput() {
            let model = {};

            for (let field of warehouseFields) {
                let value = getInputValue(`pl-default-warehouse-${field}`);
                if (value === '' && requiredFields.indexOf(field) !== -1) {
                    value = null;
                }

                if (value && field === 'phone' && !isPhoneValid(value)) {
                    value = null;
                }

                model[field] = value;
            }

            return model;
        }

        /**
         * Retrieves input field's value.
         *
         * @param {string} input
         * @return {string}
         */
        function getInputValue(input) {
            return templateService.getComponent(input, page).value;
        }

        /**
         * Validates phone number.
         *
         * @param {string} value
         * @return {boolean}
         */
        function isPhoneValid(value) {
            let regex = /^(\+|\/|\.|-|\(|\)|\d)+$/gm;

            if (!regex.test(value)) {
                return false;
            }

            let number = /\d/gm;

            return (value.match(number) || []).length > 2;
        }
    }


    Packlink.DefaultWarehouseController = DefaultWarehouseController;
})();