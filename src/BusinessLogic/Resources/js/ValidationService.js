var Packlink = window.Packlink || {};

(function () {
    function ValidationService() {
        let translationService = Packlink.translationService,
            templateService = Packlink.templateService;

        this.validateRequiredField = function (input) {
            if (input.value === '') {
                templateService.setError(input, translationService.translate('validation.requiredField'));

                return false;
            }

            return true;
        }

        this.validateEmail = function (input) {
            let regex = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;

            if (!regex.test(String(input.value).toLowerCase())) {
                templateService.setError(input, translationService.translate('validation.invalidEmail'));

                return false;
            }

            return true;
        }

        this.validatePhone = function (input) {
            let regex = /^(\ |\+|\/|\.\|-|\(|\)|\d)+$/m;

            if (!regex.test(String(input.value).toLowerCase())) {
                templateService.setError(input, translationService.translate('validation.invalidPhone'));

                return false;
            }

            return true;
        }

        this.validatePasswordLength = function (input) {
            let minLength = input.dataset.minLength;

            if (input.value.length < minLength) {
                templateService.setError(input, translationService.translate('validation.shortPassword', [minLength]));

                return false;
            }

            return true;
        };
    }

    Packlink.validationService = new ValidationService();
})();