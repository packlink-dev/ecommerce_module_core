if (!window.Packlink) {
    window.Packlink = {};
}

(function () {
    /**
     * @param {{getUrl: string, submitUrl: string, getSupportedCountriesUrl: string, getCustomData: string}} configuration
     *
     * @constructor
     */
    function CustomsController(configuration) {
        const templateService = Packlink.templateService,
            utilityService = Packlink.utilityService,
            ajaxService = Packlink.ajaxService,
            validationService = Packlink.validationService,
            translator = Packlink.translationService,
            state = Packlink.state;

        this.modelFields = [
            'default_reason',
            'default_sender_tax_id',
            'default_receiver_user_type',
            'default_receiver_tax_id',
            'default_tariff_number',
            'default_country',
            'mapping_receiver_tax_id',
        ];

        this.pageId = 'pl-customs-page';

        let page,
            currentCountry,
            receiverTaxIdValue;

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

            page = templateService.getMainPage();
            const submitButton = templateService.getComponent('pl-page-submit-btn');
            submitButton.addEventListener('click', submitPage, true);

            setSpecificFields(response);

            const backButton = templateService.getMainPage().querySelector('.pl-sub-header button');

            backButton.addEventListener('click', () => {
                state.goToState('configuration');
            })

            utilityService.hideSpinner();
        };

        const setSpecificFields = (response) => {
            setDescriptions(response);
            currentCountry = response.default_country;
            receiverTaxIdValue = response.mapping_receiver_tax_id;

            ajaxService.get(configuration.getSupportedCountriesUrl, constructCountryDropdown);
            ajaxService.get(configuration.getCustomData, constructTaxDropdown)
        };

        const constructTaxDropdown = (response) => {
            let receiverTaxId = templateService.getComponent('pl-mapping-receiver-tax', page);

            let defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.innerText = ' ';
            receiverTaxId.appendChild(defaultOption);

            for (let i = 0; i < response.length; i++) {
                const optionElement = document.createElement('option');

                optionElement.value = response[i].value;
                optionElement.innerText = response[i].name;

                if (response[i].value === receiverTaxIdValue) {
                    optionElement.selected = true;
                }

                receiverTaxId.appendChild(optionElement);
            }

            receiverTaxId.addEventListener('change', function () {
                receiverTaxIdValue = receiverTaxId.value;
            })
        };

        const constructCountryDropdown = (response) => {
            let countryInput = templateService.getComponent('pl-default-country', page);

            let defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.innerText = ' ';
            countryInput.appendChild(defaultOption);

            for (let i = 0; i < response.length; i++) {
                const supportedCountry = response[i];
                const optionElement = document.createElement('option');

                optionElement.value = supportedCountry;
                optionElement.innerText = translator.translate('countries.' + supportedCountry);

                if (supportedCountry === currentCountry) {
                    optionElement.selected = true;
                }

                countryInput.appendChild(optionElement);
            }

            countryInput.addEventListener('change', function () {
                currentCountry = countryInput.value;
            });
        };

        const setDescriptions = (response) => {
            const defaultDescription = templateService.getComponent('pl-default-desc', page),
                mappingDescription = templateService.getComponent('pl-mapping-desc', page);

            defaultDescription.innerHTML = translator.translate('customs.description', [response['system']]);
            mappingDescription.innerHTML = translator.translate('customs.mappingDescription', [response['system']]);
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
                () => {
                    state.goToState('configuration');
                },
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
                model[field] = form[field].value;
            }

            return model;
        };
    }

    Packlink.CustomsController = CustomsController;
})();