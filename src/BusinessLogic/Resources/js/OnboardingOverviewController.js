var Packlink = window.Packlink || {};

(function () {
    function OnboardingOverviewController(configuration) {

        const templateService = Packlink.templateService,
            ajaxService = Packlink.ajaxService,
            state = Packlink.state,
            templateId = 'pl-onboarding-overview-page';

        let defaultParcel,
            defaultWarehouse;

        /**
         * Displays page content.
         */
        this.display = function () {
            ajaxService.get(configuration.defaultParcelGet, fetchDefaultWarehouse);
        };

        function fetchDefaultWarehouse(response) {
            defaultParcel = response;
            ajaxService.get(configuration.defaultWarehouseGet, initializePage);
        }

        function initializePage(response) {
            defaultWarehouse = response;
            templateService.setCurrentTemplate(templateId);
            let submitBtn = templateService.getComponent('pl-onboarding-overview-button'),
                defaultParcelBtns = document.querySelectorAll('.pl-onboarding-overview-list .pl-go-to-default-parcel');

            defaultParcelBtns.forEach(function (btn) {
                btn.addEventListener('click', goToDefaultParcelForm);
            });

            if (defaultParcel && defaultParcel.hasOwnProperty('weight') && defaultParcel.weight !== null) {
                setRequiredInfoPopulatedState('pl-parcel-details', 'pl-parcel-wrapper');

                let weightPlaceholder = templateService.getComponent('pl-parcel-weight'),
                    heightPlaceholder = templateService.getComponent('pl-parcel-height'),
                    widthPlaceholder = templateService.getComponent('pl-parcel-width'),
                    lengthPlaceholder = templateService.getComponent('pl-parcel-length');

                weightPlaceholder.innerHTML = defaultParcel.weight;
                heightPlaceholder.innerHTML = defaultParcel.height;
                widthPlaceholder.innerHTML = defaultParcel.width;
                lengthPlaceholder.innerHTML = defaultParcel.length;
            } else {
                setMissingInfoState('pl-parcel-wrapper');
            }

            if (defaultWarehouse && defaultWarehouse.hasOwnProperty('name') && defaultWarehouse.name !== null) {
                setRequiredInfoPopulatedState('pl-wh-details', 'pl-wh-wrapper');

                let aliasPlaceholder = templateService.getComponent('pl-wh-alias'),
                    userPlaceholder = templateService.getComponent('pl-wh-user'),
                    companyPlaceholder = templateService.getComponent('pl-wh-company');

                aliasPlaceholder.innerHTML = defaultWarehouse.alias;
                userPlaceholder.innerHTML = defaultWarehouse.name + ' ' + defaultWarehouse.surname;
                companyPlaceholder.innerHTML = defaultWarehouse.company;
            } else {
                setMissingInfoState('pl-wh-wrapper');
            }

            function setMissingInfoState(parentClass) {
                let missingInfo = document.querySelector('.' + parentClass + ' .pl-onboarding-missing-info');
                missingInfo.classList.remove('pl-display-none');

                let indicator = document.querySelector('.' + parentClass + ' .pl-info-icon-x');
                indicator.classList.remove('pl-display-none');

                submitBtn.disabled = true;
            }
        }

        function setRequiredInfoPopulatedState(detailsWrapperId, indicatorWrapperClass) {
            let parcelInfo = templateService.getComponent(detailsWrapperId);
            parcelInfo.classList.remove('pl-display-none');

            let indicator = document.querySelector('.' + indicatorWrapperClass + ' .pl-info-icon-ok');
            indicator.classList.remove('pl-display-none');
        }

        function goToDefaultParcelForm() {
            state.goToState('default-parcel');
        }
    }

    Packlink.OnboardingOverviewController = OnboardingOverviewController;
})();
