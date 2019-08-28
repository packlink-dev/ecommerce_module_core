var Packlink = window.Packlink || {};

(function () {
    function DefaultWarehouseController(configuration) {
        const warehouseFields = [
            'alias',
            'name',
            'surname',
            'company',
            'address',
            'phone',
            'email'
        ];

        const requiredFields = [
            'alias',
            'name',
            'surname',
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

        let currentPostalCode = '';
        let currentCity = '';

        let searchTerm = '';

        let postalCodeInput = null;

        /**
         * Displays page content.
         */
        this.display = function () {
            page = templateService.setTemplate('pl-default-warehouse-template');
            utilityService.showSpinner();
            ajaxService.get(configuration.getUrl, constructPage);
        };

        /**
         * Attaches event handler to submit button.
         * Fills form with existing warehouse data retrieved from server.
         *
         * @param response
         */
        function constructPage(response) {
            country = response['country'];

            for (let field of warehouseFields) {
                let input = templateService.getComponent('pl-default-warehouse-' + field, page);
                input.addEventListener('blur', onBlurHandler, true);
                input.addEventListener('focus', onPostalCodeBlur);
                if (response[field]) {
                    input.value = response[field];
                }
            }

            postalCodeInput = templateService.getComponent('pl-default-warehouse-postal_code', page);
            if (response['postal_code'] && response['city']) {
                currentPostalCode = response['postal_code'];
                currentCity = response['city'];
                postalCodeInput.value = currentPostalCode + ' - ' + currentCity;
            }

            postalCodeInput.addEventListener('focus', onPostalCodeFocus);
            postalCodeInput.addEventListener(
                'click',
                function (event) {
                    event.stopPropagation();
                }
            );
            document.addEventListener('click', onPostalCodeBlur);
            postalCodeInput.addEventListener('keyup', utilityService.debounce(250, onPostalCodeSearch));
            postalCodeInput.addEventListener('keyup', autocompleteNavigate);

            templateService.getComponent('data-pl-id', page, 'search-icon').addEventListener(
                'click',
                function (event) {
                    event.stopPropagation();
                    postalCodeInput.focus();
                }
            );

            let submitButton = templateService.getComponent(
                'pl-default-warehouse-submit-btn',
                page
            );

            submitButton.addEventListener('click', handleSubmitButtonClicked, true);
            utilityService.configureInputElements();
            utilityService.hideSpinner();

            postalCodeInput.addEventListener(
                'focusout',
                function () {
                    postalCodeInput.value = ' ';
                },
                true);
        }

        function onPostalCodeFocus() {
            postalCodeInput.value = '';
            searchTerm = '';
        }

        function onPostalCodeBlur(event) {
            if (event) {
                event.stopPropagation();
            }

            searchTerm = '';
            let autocompleteList = templateService.getComponent('pl-postal-codes-autocomplete', page);

            if (autocompleteList) {
                autocompleteList.remove();
            }

            postalCodeInput.value = currentPostalCode + ' - ' + currentCity;
            templateService.removeError(postalCodeInput);
            utilityService.configureInputElements();
        }

        function onPostalCodeSearch(event) {
            searchTerm = event.target.value;
            if (searchTerm.length < 3 || [13, 27, 38, 40].indexOf(event.keyCode) !== -1) {
                return;
            }

            ajaxService.post(configuration.searchPostalCodesUrl, {query: searchTerm}, renderPostalCodesAutocomplete);
        }

        function renderPostalCodesAutocomplete(response) {
            let oldAutocomplete = templateService.getComponent('pl-postal-codes-autocomplete', page);

            if (oldAutocomplete) {
                oldAutocomplete.remove();
            }

            if (document.activeElement !== postalCodeInput) {
                return;
            }

            let newAutoComplete = createAutoCompleteNode();

            createAutoCompleteListElements(newAutoComplete, response);

            postalCodeInput.after(newAutoComplete);
        }

        function createAutoCompleteNode() {
            let node = document.createElement('ul');
            node.classList.add('pl-autocomplete-list');
            node.setAttribute('id', 'pl-postal-codes-autocomplete');

            return node;
        }

        function createAutoCompleteListElements(autoCompleteList, data) {
            for (let elem of data) {
                let listElement = document.createElement('li');

                listElement.classList.add('pl-autocomplete-element');
                listElement.setAttribute('data-pl-postal_code', elem['zipcode']);
                listElement.setAttribute('data-pl-city', elem['city']);

                listElement.innerHTML = elem['zipcode'] + ' - ' + elem['city'];

                listElement.addEventListener('mouseover', function (event) {
                    onAutoCompleteFocusChange(event, autoCompleteList);
                });

                listElement.addEventListener('click', onPostalCodeSelected);

                autoCompleteList.appendChild(listElement);
            }

            let firstElem = autoCompleteList.firstChild;
            if (firstElem) {
                firstElem.classList.add('focus');
            }
        }

        function onAutoCompleteFocusChange(event, autoCompleteList) {
            for (let listElement of autoCompleteList.childNodes) {
                if (listElement.classList && listElement.classList.contains('focus')) {
                    listElement.classList.remove('focus');
                }
            }

            event.target.classList.add('focus');
        }

        function onPostalCodeSelected(event) {
            currentCity = event.target.getAttribute('data-pl-city');
            currentPostalCode = event.target.getAttribute('data-pl-postal_code');

            postalCodeInput.value = currentPostalCode + ' - ' + currentCity;
        }

        function autocompleteNavigate(event) {
            //esc
            if (event.keyCode === 27) {
                postalCodeInput.blur();
                page.click();

                return true;
            }

            let autocomplete = templateService.getComponent('pl-postal-codes-autocomplete', page);
            if (!autocomplete) {
                return true;
            }

            let focused = autocomplete.querySelector('.focus');

            if (!focused) {
                return true;
            }

            //enter
            if (event.keyCode === 13) {
                postalCodeInput.blur();
                focused.click();

                return true;
            }

            // up arrow
            if (event.keyCode === 38) {
                let prevSibling = focused.previousSibling;
                if (prevSibling) {
                    prevSibling.scrollIntoView({
                        behavior: 'auto',
                        block: 'center',
                        inline: 'center'
                    });
                    prevSibling.classList.add('focus');
                    focused.classList.remove('focus');
                }

                return true;
            }

            // down arrow
            if (event.keyCode === 40) {
                let nextSibling = focused.nextSibling;
                if (nextSibling) {
                    nextSibling.scrollIntoView({
                        behavior: 'auto',
                        block: 'center',
                        inline: 'center'
                    });
                    nextSibling.classList.add('focus');
                    focused.classList.remove('focus');
                }
            }
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
                        templateService.getComponent('pl-default-warehouse-' + field, page),
                        Packlink.errorMsgs.required
                    );
                    isValid = false;
                } else {
                    templateService.removeError(templateService.getComponent('pl-default-warehouse-' + field, page));
                }
            }

            if (!currentCity || !currentPostalCode) {
                isValid = false;
                templateService.setError(postalCodeInput, Packlink.errorMsgs.required);
            } else {
                templateService.removeError(postalCodeInput);
            }

            if (isValid) {
                utilityService.showSpinner();
                model['country'] = country;
                model['postal_code'] = currentPostalCode;
                model['city'] = currentCity;

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
                                let input = templateService.getComponent('pl-default-warehouse-' + field, page);
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
                let value = getInputValue('pl-default-warehouse-' + field);
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