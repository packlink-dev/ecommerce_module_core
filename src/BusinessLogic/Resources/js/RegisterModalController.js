if (!window.Packlink) {
    window.Packlink = {};
}

(function () {

    /**
     * Country object received from the back end.
     *
     * @typedef {{
     *  name: string,
     *  code: string,
     *  postal_code: string
     * }} Country
     */

    /**
     * Controller that displays shipping country selector modal.
     *
     * @constructor
     * @param {string} modalTemplateId
     * @param {string} listOfCountriesUrl
     */
    function RegisterModalController(modalTemplateId, listOfCountriesUrl) {
        // noinspection JSCheckFunctionSignatures
        const ajaxService = Packlink.ajaxService,
            translator = Packlink.translationService,
            templateService = Packlink.templateService,
            utilityService = Packlink.utilityService,
            modal = new Packlink.modalService({
                title: translator.translate('register.chooseYourCountry'),
                content: Packlink.templateService.getTemplate('pl-register-modal'),
                onOpen: (modal) => {
                    ajaxService.get(listOfCountriesUrl, (response) => {
                        populateCountryList(modal, response);
                    });
                }
            });

        /**
         * Displays countries in the modal body.
         *
         * @param {HTMLElement} modal
         * @param {Country[]} response
         */
        const populateCountryList = (modal, response) => {
            let countryList = modal.querySelector('.pl-register-country-list-wrapper'),
                template = countryList.querySelector('#country-template'),
                countryFilter = modal.querySelector('#pl-country-filter');

            countryFilter.addEventListener('input', filterCountries);

            response.forEach((country) => {
                let countryElement = document.createElement('div');

                countryElement.innerHTML = template.innerHTML.replace('$code', country.code)
                    .replace('logo_url', templateService.replaceResourcesUrl('{$BASE_URL$}/images/flags/' + country.code + '.svg'))
                    .replace(/country_name/g, country.name);

                countryElement.firstElementChild.addEventListener('click', () => handleCountrySelected(country));
                countryList.appendChild(countryElement.firstElementChild);
            });

            utilityService.hideSpinner();
        };

        /**
         * Handles click on a country.
         *
         * @param {Country} country
         */
        const handleCountrySelected = (country) => {
            modal.close();
            Packlink.state.goToState('register', {country: country.code});
        };

        /**
         * Filters country list on user input.
         *
         * @param event
         */
        const filterCountries = (event) => {
            let filter = event.target.value.toLowerCase();

            let countries = document.querySelectorAll('.pl-register-country-list-wrapper .pl-country');

            countries.forEach((country) => {
                if (filter === ''
                    || country.dataset.code.toLowerCase().startsWith(filter)
                    || country.querySelector('.pl-country-name').innerText.toLowerCase().search(filter) !== -1
                ) {
                    country.classList.remove('pl-hidden');
                } else {
                    country.classList.add('pl-hidden');
                }
            });
        };

        /**
         * The main entry point for controller.
         */
        this.display = () => {
            utilityService.showSpinner();
            modal.open();
        };
    }

    Packlink.RegisterModalController = RegisterModalController;
})();
