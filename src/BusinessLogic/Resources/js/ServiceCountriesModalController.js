if (!window.Packlink) {
    window.Packlink = {};
}

(function () {
    /**
     * @typedef ServiceCountriesModalControllerConfiguration
     * @property {string} getCountriesListUrl
     */

    /**
     * @param {ServiceCountriesModalControllerConfiguration} configuration
     * @constructor
     */
    function ServiceCountriesModalController(configuration) {
        const templateService = Packlink.templateService,
            ajaxService = Packlink.ajaxService,
            utilityService = Packlink.utilityService,
            translator = Packlink.translationService;

        /**
         * @type ShippingService
         */
        let serviceModel = {};

        /**
         * Displays page content.
         *
         * @param {{service: ShippingService, onSave: function(ShippingService)}} config
         */
        this.display = (config) => {
            utilityService.showSpinner();
            serviceModel = config.service;
            const modal = new Packlink.modalService({
                content: templateService.getTemplate('pl-countries-selection-modal'),
                canClose: false,
                fullWidthBody: true,
                title: translator.translate('shippingServices.selectCountriesHeader'),
                buttons: [
                    {
                        title: translator.translate('general.accept'),
                        primary: true,
                        onClick: () => {
                            saveCountriesSelection(modal, config.onSave);
                        }
                    },
                    {
                        title: translator.translate('general.cancel'),
                        onClick: () => {
                            modal.close();
                        }
                    }
                ],
                onOpen: () => {
                    ajaxService.get(configuration.getCountriesListUrl, setCountriesSelectionInitialState);
                }
            });

            modal.open();
        };

        /**
         * Saves countries selection.
         *
         * @param {ModalService} modal
         * @param {function(ShippingService)} onSave
         */
        const saveCountriesSelection = (modal, onSave) => {
            const countriesSelectionForm = templateService.getComponent('pl-countries-selection-form'),
                allCountries = countriesSelectionForm.querySelectorAll('.pl-shipping-country-selection-wrapper input'),
                selectedCountries = countriesSelectionForm.querySelectorAll('.pl-shipping-country-selection-wrapper input:checked'),
                isShipToAllCountries = allCountries.length === selectedCountries.length;

            if (!isShipToAllCountries) {
                if (selectedCountries.length === 0) {
                    showValidationMessage();
                    return;
                }

                serviceModel.shippingCountries = [];
                selectedCountries.forEach(
                    (input) => {
                        serviceModel.shippingCountries.push(input.name);
                    }
                );
            }

            serviceModel.isShipToAllCountries = isShipToAllCountries;

            onSave(serviceModel);
            modal.close();
        };

        /**
         * Sets countries selection initial state.
         *
         * @param {[{value: string, label: string}]} listOfCountries
         */
        const setCountriesSelectionInitialState = (listOfCountries) => {
            const shippingCountryWrapper = templateService.getComponent('pl-shipping-country-selection-wrapper');
            shippingCountryWrapper.innerHTML = '';
            listOfCountries.forEach((country) => {
                shippingCountryWrapper.innerHTML += '<div class="pl-checkbox pl-country-checkbox-wrapper pl-no-margin">' +
                    '<input type="checkbox" name="' + country.value + '" id="pl-' + country.value + '">' +
                    '<label for="pl-' + country.value + '">' +
                    country.label +
                    '</label>' +
                    '</div>';
            });

            const countriesSelectionForm = templateService.getComponent('pl-countries-selection-form'),
                countryInputs = countriesSelectionForm.querySelectorAll('.pl-shipping-country-selection-wrapper input');

            countriesSelectionForm['isShipToAllCountries'].checked = serviceModel.isShipToAllCountries;

            if (serviceModel.isShipToAllCountries) {
                countryInputs.forEach((input) => {
                    input.checked = true;
                });
            } else {
                serviceModel.shippingCountries.forEach((country) => {
                    const cBox = countriesSelectionForm.querySelector('#pl-' + country);
                    if (cBox) {
                        cBox.checked = true;
                    }
                });

                handleCountrySelectionChanged();
            }

            setCountryChangeEvents(countryInputs);
            setShipToAllCountriesChangeEvent(countriesSelectionForm);
            utilityService.hideSpinner();
        };

        /**
         * Handles country selection changed.
         */
        const handleCountrySelectionChanged = () => {
            const countriesSelectionForm = templateService.getComponent('pl-countries-selection-form');
            const selectedCountries = countriesSelectionForm.querySelectorAll('.pl-shipping-country-selection-wrapper input:checked');
            const label = templateService.getComponent('pl-check-all-countries');
            const countryInputs = countriesSelectionForm.querySelectorAll('.pl-shipping-country-selection-wrapper input');
            countriesSelectionForm['isShipToAllCountries'].checked = selectedCountries.length > 0;

            if (selectedCountries.length === countryInputs.length || selectedCountries.length === 0) {
                label.innerHTML = translator.translate('shippingServices.selectAllCountries');
            } else if (selectedCountries.length === 1) {
                label.innerHTML = translator.translate('shippingServices.oneCountrySelected');
            } else {
                label.innerHTML = translator.translate('shippingServices.selectedCountries', [selectedCountries.length]);
            }
        };

        /**
         * Sets event listeners for country change.
         *
         * @param {[HTMLInputElement]}countryInputs
         */
        const setCountryChangeEvents = (countryInputs) => {
            countryInputs.forEach((input) => {
                markSelectedCountry(input);

                input.addEventListener('change', () => {
                    markSelectedCountry(input);
                    handleCountrySelectionChanged();
                });
            });
        };

        /**
         * Sets event listener for shipToAllCountries checkbox.
         *
         * @param {HTMLElement} countriesSelectionForm
         */
        const setShipToAllCountriesChangeEvent = (countriesSelectionForm) => {
            countriesSelectionForm['isShipToAllCountries'].addEventListener('change', (event) => {
                const label = templateService.getComponent('pl-check-all-countries');
                countriesSelectionForm.querySelectorAll('.pl-shipping-country-selection-wrapper input').forEach((input) => {
                    input.checked = event.target.checked;
                    if (!input.checked) {
                        label.innerHTML = translator.translate('shippingServices.selectAllCountries');
                    }
                    markSelectedCountry(input);
                });
            });
        };

        /**
         * Marks selected country.
         *
         * @param {HTMLInputElement} input
         */
        const markSelectedCountry = (input) => {
            let inputWrapper = input.parentElement;

            inputWrapper.classList.remove('pl-shipping-country-selected');
            if (input.checked) {
                inputWrapper.classList.add('pl-shipping-country-selected');
            }
        };

        const showValidationMessage = () => {
            const errorMsg = templateService.getComponent('pl-countries-alert-wrapper');
            errorMsg.querySelector('.material-icons').addEventListener('click', hideValidationMessage);
            hideValidationMessage();
            errorMsg.classList.add('visible');
            setTimeout(hideValidationMessage, 7000);
        };

        const hideValidationMessage = () => {
            const errorMsg = templateService.getComponent('pl-countries-alert-wrapper');
            if (errorMsg) {
                errorMsg.classList.remove('visible');
            }
        }
    }

    Packlink.ServiceCountriesModalController = ServiceCountriesModalController;
})();
