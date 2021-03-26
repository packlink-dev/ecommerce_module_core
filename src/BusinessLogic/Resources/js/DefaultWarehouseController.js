if (!window.Packlink) {
    window.Packlink = {};
}

(function () {
    /**
     * @typedef {{
     *  id: string,
     *  alias: string,
     *  name: string,
     *  surname: string,
     *  city: string,
     *  phone: string,
     *  country: string,
     *  company: string,
     *  postal_code: string,
     *  address: string}} Warehouse
     */

    /**
     * @param {{getUrl: string, submitUrl: string, getSupportedCountriesUrl: string, searchPostalCodesUrl: string}} configuration
     *
     * @constructor
     */
    function DefaultWarehouseController(configuration) {
        const templateService = Packlink.templateService,
            utilityService = Packlink.utilityService,
            ajaxService = Packlink.ajaxService,
            pageId = 'pl-default-warehouse-page';

        const modelFields = [
            'alias',
            'name',
            'surname',
            'company',
            'country',
            'postal_code',
            'address',
            'phone',
            'email'
        ];

        let page;

        let currentCountry;
        let currentPostalCode = '';
        let currentCity = '';

        let searchTerm = '';

        let countryInput = null;
        let postalCodeInput = null;

        // change parent's properties and methods
        const parent = new Packlink.DefaultParcelController(configuration);
        parent.modelFields = modelFields;
        parent.pageId = pageId;
        parent.pageKey = 'defaultWarehouse';

        const parentConstruct = parent.constructPage;

        parent.constructPage = (response) => {
            page = templateService.getMainPage();
            parentConstruct(response);
            setSpecificFields(response);
        };

        /**
         * Gets the form field values model.
         *
         * @param {HTMLElement} form
         * @return {{}}
         */
        parent.getFormFields = (form) => {
            let model = {};

            for (let field of modelFields) {
                model[field] = form[field].value;
            }

            return model;
        };

        this.display = parent.display;

        /**
         * Sets up specific fields.
         *
         * @param {Warehouse} warehouse
         */
        const setSpecificFields = (warehouse) => {
            currentCountry = warehouse.country;

            constructPostalCodeInput(warehouse.postal_code, warehouse.city);

            utilityService.hideSpinner();

            ajaxService.get(configuration.getSupportedCountriesUrl, constructCountryDropdown);
        };

        /**
         * Builds a warehouse country dropdown and populates it with all supported countries.
         *
         * @param {{}} response
         */
        const constructCountryDropdown = (response) => {
            countryInput = templateService.getComponent('pl-default-warehouse-country', page);

            let defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.innerText = ' ';
            countryInput.appendChild(defaultOption);

            for (let code in response) {
                if (!response.hasOwnProperty(code)) {
                    continue;
                }

                /** @var {{name: string, code: string, postal_code: string, platform_country: string}} */
                const supportedCountry = response[code];
                const optionElement = document.createElement('option');

                optionElement.value = supportedCountry.code;
                optionElement.innerText = supportedCountry.name;

                if (supportedCountry.code === currentCountry) {
                    optionElement.selected = true;
                }

                countryInput.appendChild(optionElement);
            }

            countryInput.addEventListener('change', onCountryChange);
            postalCodeInput.disabled = countryInput.value === '';
        };

        /**
         * Resets the postal code input.
         */
        const onCountryChange = () => {
            currentCountry = countryInput.value;
            currentPostalCode = '';
            currentCity = '';
            postalCodeInput.value = '-';
            postalCodeInput.disabled = countryInput.value === '';
        };

        /**
         * Constructs postal code input and attaches event handlers to it.
         *
         * @param {string} postalCode
         * @param {string} city
         */
        const constructPostalCodeInput = (postalCode, city) => {
            postalCodeInput = templateService.getComponent('pl-default-warehouse-postal_code', page);
            if (postalCode && city) {
                currentPostalCode = postalCode;
                currentCity = city;
                postalCodeInput.value = currentPostalCode + ' - ' + currentCity;
            }

            postalCodeInput.addEventListener('focus', onPostalCodeFocus);
            postalCodeInput.addEventListener('click', (event) => {
                event.stopPropagation();
            });

            page.addEventListener('click', onPostalCodeBlur);
            postalCodeInput.addEventListener('keyup', utilityService.debounce(250, onPostalCodeSearch));
            postalCodeInput.addEventListener('keyup', autocompleteNavigate);
            postalCodeInput.addEventListener('focusout', () => {
                if (postalCodeInput.value) {
                    // reset the value
                    postalCodeInput.value = currentPostalCode + ' - ' + currentCity;
                }
            }, true);

            postalCodeInput.parentElement.querySelector('i').addEventListener('click', (event) => {
                event.stopPropagation();
                postalCodeInput.focus();
            });
        };

        const onPostalCodeFocus = () => {
            postalCodeInput.value = currentPostalCode;
            searchTerm = '';
        };

        const onPostalCodeBlur = (event) => {
            if (event) {
                event.stopPropagation();
            }

            searchTerm = '';
            let autocompleteList = templateService.getComponent('pl-postal-codes-autocomplete', page);

            if (autocompleteList) {
                autocompleteList.remove();
            }
        };

        const onPostalCodeSearch = (event) => {
            searchTerm = event.target.value;
            if (searchTerm.length < 3 || [13, 27, 38, 40].indexOf(event.keyCode) !== -1) {
                return;
            }

            ajaxService.post(configuration.searchPostalCodesUrl, {
                query: searchTerm,
                country: countryInput.value
            }, renderPostalCodesAutocomplete);
        };

        const renderPostalCodesAutocomplete = (response) => {
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
        };

        const createAutoCompleteNode = () => {
            let node = document.createElement('ul');
            node.classList.add('pl-autocomplete-list');
            node.setAttribute('id', 'pl-postal-codes-autocomplete');

            return node;
        };

        const createAutoCompleteListElements = (autoCompleteList, data) => {
            for (let elem of data) {
                let listElement = document.createElement('li');

                listElement.classList.add('pl-autocomplete-list-item');
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
                firstElem.classList.add('pl-focus');
            }
        };

        const onAutoCompleteFocusChange = (event, autoCompleteList) => {
            for (let listElement of autoCompleteList.childNodes) {
                if (listElement.classList && listElement.classList.contains('pl-focus')) {
                    listElement.classList.remove('pl-focus');
                }
            }

            event.target.classList.add('pl-focus');
        };

        const onPostalCodeSelected = (event) => {
            currentCity = event.target.getAttribute('data-pl-city');
            currentPostalCode = event.target.getAttribute('data-pl-postal_code');

            postalCodeInput.value = currentPostalCode + ' - ' + currentCity;
        };

        const autocompleteNavigate = (event) => {
            // noinspection JSDeprecatedSymbols
            const keyCode = event.keyCode;
            //esc
            if (keyCode === 27) {
                postalCodeInput.blur();
                page.click();

                return true;
            }

            let autocomplete = templateService.getComponent('pl-postal-codes-autocomplete', page);
            if (!autocomplete) {
                return true;
            }

            let focused = autocomplete.querySelector('.pl-focus');
            if (!focused) {
                return true;
            }

            switch (keyCode) {
                case 13:
                    //enter
                    postalCodeInput.blur();
                    focused.click();
                    break;
                case 38:
                    // up arrow
                    focusAutocompleteItem(focused.previousSibling, focused);
                    break;
                case 40:
                    // down arrow
                    focusAutocompleteItem(focused.nextSibling, focused);
                    break;
            }
        };

        const focusAutocompleteItem = (nextItem, prevItem) => {
            if (nextItem) {
                nextItem.scrollIntoView({
                    behavior: 'auto',
                    block: 'center',
                    inline: 'center'
                });
                nextItem.classList.add('pl-focus');
                prevItem.classList.remove('pl-focus');
            }
        };
    }

    Packlink.DefaultWarehouseController = DefaultWarehouseController;
})();
