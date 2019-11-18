var Packlink = window.Packlink || {};

(function () {
    /**
     * Controller that displays shipping country selector pop up.
     *
     * @constructor
     */
    function CountrySelectorController() {
        let templateService = Packlink.templateService;
        let extensionPoint = templateService.getComponent('pl-allowed-shipping-countries-ep');

        this.display = display;
        this.destroy = destroy;

        /**
         * Displays country selector.
         *
         * @param {array} availableCountries List of available countries in system.
         * @param {array} selectedCountries List of selected countries.
         * @param {function} saveCallback callback when save button is clicked.
         * @param {function} cancelCallback callback when cancel button is clicked.
         */
        function display(availableCountries, selectedCountries, saveCallback, cancelCallback) {
            templateService.setTemplate('pl-allowed-countries-modal-template', 'pl-allowed-shipping-countries-ep', true);

            let saveBtn = templateService.getComponent('pl-countries-selector-save-btn', extensionPoint);
            saveBtn.addEventListener('click', onSaveButtonClicked);

            if (selectedCountries.length === 0) {
                saveBtn.disabled = true;
            }

            let closeBtn = templateService.getComponent('pl-close-modal-btn', extensionPoint);
            closeBtn.addEventListener('click', onCancelClicked);

            let cancelBtn = templateService.getComponent('pl-countries-selector-cancel-btn');
            cancelBtn.addEventListener('click', onCancelClicked);

            let selector = templateService.getComponent('pl-countries-selector', extensionPoint);

            for (let country of availableCountries) {
                let option = document.createElement('option');
                option.value = country.value;
                option.innerHTML = country.label;

                if (selectedCountries.indexOf(country.value) !== -1) {
                    option.selected = true;
                }

                selector.appendChild(option);
            }

            selector.addEventListener('change', onChangeSelector);

            function onCancelClicked() {
                cancelCallback();
            }

            function onSaveButtonClicked() {
                if (selector.selectedOptions.length === 0) {
                    saveBtn.disabled = true;
                } else {
                    let result = [];
                    for (let option of selector.selectedOptions) {
                        result.push(option.value);
                    }

                    saveCallback(result);
                }
            }

            function onChangeSelector() {
                saveBtn.disabled = selector.selectedOptions.length === 0;
            }
        }

        function destroy() {
            while (extensionPoint.firstChild) {
                extensionPoint.firstChild.remove();
            }
        }
    }

    Packlink.CountrySelectorController = CountrySelectorController;
})();
