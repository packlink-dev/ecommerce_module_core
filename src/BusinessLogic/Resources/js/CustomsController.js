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
        ];

        this.pageId = 'pl-customs-page';

        let page,
            currentCountry,
            mappingFieldValues = {};

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
            mappingFieldValues = response;

            ajaxService.get(configuration.getSupportedCountriesUrl, constructCountryDropdown);
            ajaxService.get(configuration.getCustomData, constructMappingFields);
        };

        /**
         * Renders a data-mapping <select> for each platform-supplied field
         * definition. The set of fields, their labels and their options are
         * entirely platform-driven; core neither hardcodes nor assumes any
         * of them.
         *
         * @param {Array<{field: string, label: string, options: Array<{value: string, name: string}>}>} response
         */
        const constructMappingFields = (response) => {
            if (!Array.isArray(response)) {
                return;
            }

            const container = templateService.getComponent('pl-mapping-fields', page);

            for (let fieldDefinition of response) {
                const fieldName = fieldDefinition.field,
                    selectId = 'pl-mapping-' + fieldName;

                const formGroup = document.createElement('div');
                formGroup.className = 'pl-form-group';

                const select = document.createElement('select');
                select.id = selectId;
                select.name = fieldName;

                let defaultOption = document.createElement('option');
                defaultOption.value = '';
                defaultOption.innerText = ' ';
                select.appendChild(defaultOption);

                for (let option of fieldDefinition.options) {
                    const optionElement = document.createElement('option');

                    optionElement.value = option.value;
                    optionElement.innerText = option.name;

                    if (option.value === mappingFieldValues[fieldName]) {
                        optionElement.selected = true;
                    }

                    select.appendChild(optionElement);
                }

                const icon = document.createElement('i');
                icon.className = 'material-icons';
                icon.innerText = 'expand_more';

                const label = document.createElement('label');
                label.className = 'pl-customs-label';
                label.setAttribute('for', selectId);
                label.setAttribute('title', fieldDefinition.label);
                label.innerText = fieldDefinition.label;

                formGroup.appendChild(select);
                formGroup.appendChild(icon);
                formGroup.appendChild(label);
                container.appendChild(formGroup);

                this.modelFields.push(fieldName);
            }
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