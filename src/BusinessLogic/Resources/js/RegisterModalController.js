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
            let countryList = document.querySelector('.pl-register-country-list-wrapper');

            if (!countryList) {
                modalTemplate.querySelector('.pl-modal-body').innerHTML +=
                    '<div class="pl-register-country-list-wrapper"></div>';

                countryList = document.querySelector('.pl-register-country-list-wrapper');
            }

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

        function close() {
            modalTemplate.classList.remove('enabled');
        }
    }

    Packlink.RegisterModalController = RegisterModalController;
})();