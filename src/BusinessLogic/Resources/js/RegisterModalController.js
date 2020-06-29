var Packlink = window.Packlink || {};

(function () {

    /**
     * Controller that displays shipping country selector pop up.
     *
     * @constructor
     */
    function RegisterModalController(modalTemplateId, listOfCountriesUrl, logoPath) {

        const ajaxService = Packlink.ajaxService,
            translationService = Packlink.translationService,
            modalTemplate = document.getElementById(modalTemplateId);

        this.display = function() {
            modalTemplate.classList.remove('enabled');
            modalTemplate.classList.add('enabled');
            modalTemplate.querySelector('.pl-modal-close-button').addEventListener('click', close);
            modalTemplate.querySelector('.pl-modal-title').innerHTML = translationService.translate('register.chooseYourCountry');

            ajaxService.get(listOfCountriesUrl, populateCountryList);
        };

        function populateCountryList(response) {
            let countryList = document.querySelector('.pl-register-country-list-wrapper'),
                countryFilter = document.getElementById('pl-country-filter');

            if (!countryList) {
                modalTemplate.querySelector('.pl-modal-body').innerHTML +=
                    '<div class="pl-form-group">' +
                    '<label for="pl-country-filter">' +
                    translationService.translate('register.searchCountry') +
                    '</label>' +
                    '<input id="pl-country-filter" type="text" class="form-control" name="countryFilter">' +
                    '</div>' +
                    '<div class="pl-register-country-list-wrapper"></div>';

                countryList = document.querySelector('.pl-register-country-list-wrapper');
                countryFilter = document.getElementById('pl-country-filter');
            } else {
                countryFilter.value = '';
            }

            countryFilter.addEventListener('input', filterCountriesCallback);
            filterCountries('');

            if (countryList.childElementCount > 0) {
                return;
            }

            for (let code in response) {
                // noinspection JSUnfilteredForInLoop
                let supportedCountry = response[code],
                    linkElement = document.createElement('a'),
                    countryElement = document.createElement('div'),
                    imageElement = document.createElement('img'),
                    nameElement = document.createElement('div');

                linkElement.dataset.code = supportedCountry.code;
                linkElement.addEventListener('click', handleCountrySelected(supportedCountry));

                countryElement.classList.add('pl-country');

                imageElement.src = logoPath + '/' + supportedCountry.code + '.svg';
                imageElement.classList.add('pl-country-logo');
                imageElement.alt = supportedCountry.name;

                countryElement.appendChild(imageElement);

                nameElement.classList.add('pl-country-name');
                nameElement.innerText = supportedCountry.name;

                countryElement.appendChild(nameElement);
                linkElement.appendChild(countryElement);
                countryList.appendChild(linkElement);
            }
        }

        function handleCountrySelected(supportedCountry) {
            return function() {
                close();
                Packlink.state.goToState('register', {country: supportedCountry});
            }
        }

        function filterCountriesCallback(event) {
            return filterCountries(event.target.value);

        }

        function filterCountries(filter) {
            filter = filter.toLowerCase();

            let countries = document.querySelectorAll('.pl-register-country-list-wrapper a');

            for (let i = 0; i < countries.length; i++) {
                if (countries[i].dataset.code.toLowerCase().startsWith(filter) ||
                    countries[i].querySelector('.pl-country-name').innerText.toLowerCase().startsWith(filter) ||
                    filter === ''
                ) {
                    countries[i].classList.remove('pl-hidden');
                } else {
                    countries[i].classList.add('pl-hidden');
                }
            }
        }

        function close() {
            modalTemplate.classList.remove('enabled');
        }
    }

    Packlink.RegisterModalController = RegisterModalController;
})();