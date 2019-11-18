var Packlink = window.Packlink || {};

(function () {
    function ShippingMethodsController(configuration) {
        const PRICING_POLICY_PACKLINK = 1;
        const PRICING_POLICY_PERCENT = 2;
        const PRICING_POLICY_FIXED_BY_WEIGHT = 3;
        const PRICING_POLICY_FIXED_BY_VALUE = 4;

        const STATUSES = [
            'disabled',
            'in-progress',
            'completed'
        ];

        let templateService = Packlink.templateService;
        let utilityService = Packlink.utilityService;
        let ajaxService = Packlink.ajaxService;
        let state = Packlink.state;

        let isDashboardShown = false;

        let selectedId = null;

        let currentNavTab = 'all';

        let spinnerBarrierCount = 0;
        let spinnerBarrier = getSpinnerBarrier();

        /** @var {{parcelSet, warehouseSet, shippingMethodSet}} dashboardData */
        let dashboardData = {};

        /**
         * @var {{id, name, title, logoUrl, taxClass, deliveryDescription, showLogo, deliveryType,
         * parcelDestination, parcelOrigin, selected,
         * pricePolicy, fixedPriceByWeightPolicy, fixedPriceByValuePolicy, percentPricePolicy,
         * isShipToAllCountries, shippingCountries}} methodModel
         */
        let methodModel = {};
        let taxClasses = [];

        let filters = {
            title: [],
            deliveryType: [],
            parcelOrigin: [],
            parcelDestination: []
        };

        // Element in DOM where shipping methods page content is inserted.
        let extensionPoint;

        let filtersExtensionPoint;
        let navExtensionPoint;
        let resultExtensionPoint;
        let tableExtensionPoint;
        let tableRowExtensionPoint;
        let taxSelector = null;

        let shippingMethods = {};

        let shippingMethodTemplates = {};

        let renderedShippingMethods = [];

        let autoConfigureInitialized = false;

        let countrySelector = {};
        let availableCountries = [];

        /**
         * Displays page content.
         */
        this.display = function () {
            utilityService.showSpinner();

            extensionPoint = templateService.setTemplate('pl-shipping-methods-page-template');

            filtersExtensionPoint = addStaticComponent(
                'pl-shipping-methods-filters-template',
                'pl-shipping-methods-filters-extension-point',
                'data-pl-shipping-methods-filter',
                shippingMethodFilterClickHandler
            );

            navExtensionPoint = addStaticComponent(
                'pl-shipping-methods-nav-template',
                'pl-shipping-methods-nav-extension-point',
                'data-pl-shipping-methods-nav-button',
                shippingMethodNavigationButtonClickHandler
            );

            resultExtensionPoint = addStaticComponent(
                'pl-shipping-methods-result-template',
                'pl-shipping-methods-result-extension-point'
            );

            tableExtensionPoint = addStaticComponent(
                'pl-shipping-methods-table-template',
                'pl-shipping-methods-table-extension-point'
            );

            tableRowExtensionPoint = templateService.getComponent(
                'pl-shipping-method-table-row-extension-point',
                tableRowExtensionPoint
            );

            templateService.getComponent('pl-disable-methods-modal-cancel').addEventListener(
                'click',
                hideDisableShopShippingMethodsModal
            );

            templateService.getComponent('pl-disable-methods-modal-accept').addEventListener(
                'click',
                function () {
                    utilityService.showSpinner();
                    ajaxService.get(
                        configuration.disableShopShippingMethodsUrl,
                        methodsDisabledSuccessCallback,
                        methodsDisabledFailedCallback
                    );
                }
            );

            if (configuration.hasTaxConfiguration) {
                ajaxService.get(configuration.getTaxClassesUrl, getTaxClassesSuccessHandler);
            }

            if (configuration.hasCountryConfiguration) {
                ajaxService.get(configuration.getShippingCountries, getShippingCountriesHandler);
            }

            ajaxService.get(configuration.getDashboardStatusUrl, function (response) {
                getStatusHandler(response);
                ajaxService.get(configuration.getMethodsStatusUrl, getShippingMethodsStatusHandler);
            });
        };

        /**
         * Get status of getting shipping methods task.
         *
         * @param {object} response
         */
        function getShippingMethodsStatusHandler(response) {
            if (configuration.context !== state.getContext()) {
                return;
            }

            if (response.status === 'completed') {
                ajaxService.get(configuration.getAllMethodsUrl, getShippingMethodsHandler);

                return;
            }

            if (response.status === 'failed') {
                hideDashboardModal(true);
                showNoShippingMethodsMessage();

                return;
            }

            setTimeout(
                function () {
                    ajaxService.get(configuration.getMethodsStatusUrl, getShippingMethodsStatusHandler);
                },
                1000
            );
        }

        /**
         * Get all shipping methods callback.
         *
         * @param {array} response
         */
        function getShippingMethodsHandler(response) {
            if (configuration.context !== state.getContext()) {
                return;
            }

            for (let method of response) {
                shippingMethods[method['id']] = method;
            }

            if (response.length === 0) {
                hideDashboardModal(true);
                showNoShippingMethodsMessage();

                return;
            }

            hideGettingShippingMethodsMessage();
            renderShippingMethods();
        }

        /**
         * Shows message when getting shipping services.
         */
        function showGettingShippingMethodsMessage() {
            utilityService.enableInputMask();
            templateService.getComponent('pl-getting-shipping-services', extensionPoint).classList.remove('hidden');
        }

        /**
         * Hides message when shipping services are available.
         */
        function hideGettingShippingMethodsMessage() {
            utilityService.disableInputMask();
            templateService.getComponent('pl-getting-shipping-services', extensionPoint).classList.add('hidden');
        }

        /**
         * Shows message when shipping services cannot be fetched.
         */
        function showNoShippingMethodsMessage() {
            let container = templateService.getComponent('pl-no-shipping-services', extensionPoint);
            utilityService.hideSpinner();
            hideGettingShippingMethodsMessage();
            if (container) {
                container.classList.remove('hidden');
                if (!autoConfigureInitialized) {
                    initAutoConfigure(container);
                }
            }
        }

        /**
         * Hides message when shipping services cannot be fetched.
         */
        function hideNoShippingMethodsMessage() {
            templateService.getComponent('pl-no-shipping-services', extensionPoint).classList.add('hidden');
            showGettingShippingMethodsMessage();
        }

        /**
         * Initializes the auto-configure process.
         *
         * @param {Element} [container]
         */
        function initAutoConfigure(container) {
            let configureButton = templateService.getComponent('pl-shipping-services-retry-btn', container);
            if (configureButton && configuration.autoConfigureStartUrl) {
                autoConfigureInitialized = true;
                configureButton.addEventListener('click', startAutoConfigure);
            }
        }

        /**
         * Starts the auto-configure process.
         */
        function startAutoConfigure() {
            hideNoShippingMethodsMessage();
            ajaxService.post(
                configuration.autoConfigureStartUrl,
                [],
                function success(response) {
                    if (response.success === true) {
                        ajaxService.get(configuration.getMethodsStatusUrl, getShippingMethodsStatusHandler);
                    } else {
                        showNoShippingMethodsMessage();
                    }
                },
                function error() {
                    showNoShippingMethodsMessage();
                }
            );
        }

        /**
         * Get setup status callback.
         *
         * @param {object} response
         */
        function getStatusHandler(response) {
            if (configuration.context !== state.getContext()) {
                return;
            }

            dashboardData = response;

            initSteps();

            if (!dashboardData.parcelSet || !dashboardData.warehouseSet || !dashboardData.shippingMethodSet) {
                showDashboardModal();
            } else {
                hideDashboardModal();
            }

            templateService.getComponent('pl-dashboard-modal-wrapper', extensionPoint).addEventListener(
                'click',
                hideDashboardModal
            );

            templateService.getComponent('pl-dashboard-modal', extensionPoint).addEventListener(
                'click',
                function (event) {
                    event.stopPropagation();
                }
            );

            if (spinnerBarrier === ++spinnerBarrierCount) {
                utilityService.hideSpinner();
            }
        }

        /**
         * Adds static component to shipping methods page.
         *
         * @param {string} template
         * @param {string} point
         * @param {string} [actionElementIdentifier]
         * @param {function} [actionCallback]
         *
         * @return {Element}
         */
        function addStaticComponent(template, point, actionElementIdentifier, actionCallback) {
            let staticComponentExtensionPoint = templateService.setTemplate(
                template,
                point
            );

            if (typeof actionElementIdentifier !== 'undefined') {
                let actionElements = templateService.getComponentsByAttribute(
                    actionElementIdentifier,
                    staticComponentExtensionPoint
                );

                for (let actionElement of actionElements) {
                    actionElement.addEventListener('click', actionCallback, true);
                }
            }

            return staticComponentExtensionPoint;
        }

        /**
         * Handles click event on shipping method filter.
         *
         * @param event
         */
        function shippingMethodFilterClickHandler(event) {
            let filter = event.target.getAttribute('data-pl-shipping-methods-filter');
            let filterParts = filter.split('-');
            let filterType = filterParts[0];
            let filterValue = filterParts[1];
            let filterIndex = filters[filterType].indexOf(filterValue);

            if (filterIndex === -1) {
                filters[filterType].push(filterValue);
            } else {
                filters[filterType].splice(filterIndex, 1);
            }

            renderShippingMethods();
        }

        /**
         * Performs filtering of shipping methods.
         */
        function filterMethods() {
            renderedShippingMethods = [];
            let filterTypes = Object.keys(filters);
            let result = {};

            for (let t of filterTypes) {
                result[t] = [];
                if (filters[t].length === 0) {
                    result[t] = Object.keys(shippingMethods);
                    continue;
                }

                for (let f of filters[t]) {
                    result[t].push(...applyFilter(t, f));
                }
            }

            renderedShippingMethods = result[filterTypes[0]];
            // Finds intersection of results;
            for (let i = 1; i < filterTypes.length; i++) {
                renderedShippingMethods = renderedShippingMethods.filter(
                    function (item) {
                        return result[filterTypes[i]].indexOf(item) !== -1;
                    }
                );
            }

            // Take only unique values. Rendered shipping methods variable has to behave like a set.
            // Also take current selection tab into consideration.
            renderedShippingMethods = renderedShippingMethods.filter(
                function (r, index, set) {
                    let shippingMethod = shippingMethods[r];
                    let selectRequired = currentNavTab === 'selected';

                    return (set.indexOf(r) === index) && (selectRequired && shippingMethod.selected || !selectRequired);
                }
            );
        }

        /**
         * Returns ids of shipping methods that fulfill specific filter requirement.
         *
         * @param {string} type
         * @param {string} filter
         *
         * @return {array}
         */
        function applyFilter(type, filter) {
            let methods = Object.keys(shippingMethods);

            return methods.filter(function (method) {
                let shippingMethod = shippingMethods[method];

                return (shippingMethod[type] === filter);
            });
        }

        /**
         * Handles click event on shipping method navigation button.
         *
         * @param event
         */
        function shippingMethodNavigationButtonClickHandler(event) {
            let navButton = event.target;
            let tab = navButton.getAttribute('data-pl-shipping-methods-nav-button');

            if (tab === currentNavTab) {
                return;
            }

            navButton.classList.add('selected');
            let oldTab = templateService.getComponent(
                'data-pl-shipping-methods-nav-button',
                navExtensionPoint,
                currentNavTab
            );

            oldTab.classList.remove('selected');

            currentNavTab = tab;
            renderShippingMethods();
        }

        /**
         * Renders shipping methods.
         */
        function renderShippingMethods() {
            filterMethods();

            templateService.clearComponent(tableRowExtensionPoint);
            let numberOfSelectedMethods = 0;

            for (let shippingMethod of renderedShippingMethods) {
                let row = templateService.getTemplate('pl-shipping-methods-row-template')[0];
                row.setAttribute('id', 'pl-shipping-method-row-' + shippingMethod);
                constructRow(shippingMethod, row);
                tableRowExtensionPoint.appendChild(row);
                shippingMethodTemplates[shippingMethod] = row;
                if (shippingMethods[shippingMethod].selected) {
                    numberOfSelectedMethods++;
                }
            }

            let renderedIndicator = templateService.getComponent('pl-number-showed-methods', resultExtensionPoint);
            renderedIndicator.innerHTML = renderedShippingMethods.length;
        }

        /**
         * Fills row template with concrete information such as title etc.
         * Also, attaches proper event handlers to actionable elements of row template.
         *
         * @param {int} id
         * @param {Element} template
         */
        function constructRow(id, template) {
            let shippingMethod = shippingMethods[id];

            let name = templateService.getComponent('pl-shipping-method-name', template);
            name.innerHTML = shippingMethod.name;

            let selectButton = templateService.getComponent('pl-shipping-method-select-btn', template);
            selectButton.setAttribute('data-pl-shipping-method-id', id.toString());
            selectButton.addEventListener('click', handleShippingMethodSelectClicked, true);

            templateService.getComponent('pl-logo', template).setAttribute('src', shippingMethod.logoUrl);

            if (shippingMethod.selected) {
                selectButton.classList.add('selected');
            }

            let configButton = templateService.getComponent('pl-shipping-method-config-btn', template);
            configButton.setAttribute('data-pl-shipping-method-id', id.toString());
            configButton.addEventListener('click', handleShippingMethodConfigClicked, true);

            if (shippingMethod.parcelOrigin === 'pickup') {
                templateService.getComponent('pl-pudo-icon-origin', template).classList.add('pl-pickup');
            }

            if (shippingMethod.parcelDestination === 'home') {
                templateService.getComponent('pl-pudo-icon-dest', template).classList.add('pl-pickup');
            }

            if (shippingMethod.title === 'national') {
                templateService.getComponent('pl-method-title', template).classList.add('pl-national');
            }

            templateService.getComponent('pl-delivery-type', template).innerHTML = shippingMethod.deliveryDescription;

            initPriceIndicators(shippingMethod, template);
        }

        /**
         * Displays proper pricing indicator.
         *
         * @param shippingMethod
         * @param template
         */
        function initPriceIndicators(shippingMethod, template) {
            let indicator = 'packlink';

            if (shippingMethod.pricePolicy === PRICING_POLICY_PERCENT) {
                indicator = 'percent';
            } else if (shippingMethod.pricePolicy === PRICING_POLICY_FIXED_BY_WEIGHT) {
                indicator = 'fixed-weight';
            } else if (shippingMethod.pricePolicy === PRICING_POLICY_FIXED_BY_VALUE) {
                indicator = 'fixed-value';
            }

            templateService.getComponent('data-pl-price-indicator', template, indicator).classList.add('selected');
        }

        /**
         * Handles shipping method select switch click event.
         *
         * @param event
         */
        function handleShippingMethodSelectClicked(event) {
            utilityService.showSpinner();

            selectedId = event.target.getAttribute('data-pl-shipping-method-id');

            if (!shippingMethods[selectedId].selected) {
                ajaxService.post(configuration.activateUrl, {id: selectedId}, handleActivateSuccess, handleActivateError);
            } else {
                ajaxService.post(configuration.deactivateUrl, {id: selectedId}, handleActivateSuccess, handleActivateError);
            }

            renderShippingMethods();
        }

        /**
         * Shipping method activate/deactivate success callback.
         *
         * @param response
         */
        function handleActivateSuccess(response) {
            shippingMethods[selectedId].selected = !shippingMethods[selectedId].selected;

            if (response.message) {
                utilityService.showFlashMessage(response.message, 'success');
            }

            if (shippingMethods[selectedId].selected && shouldGetShopShippingMethodCount()) {
                getShopShippingMethodCount();
            } else {
                utilityService.hideSpinner();
            }

            selectedId = null;
            renderShippingMethods();
        }

        /**
         * Retrieves number of active shipping methods.
         *
         * @return {number}
         */
        function getNumberOfActiveServices() {
            let result = 0;
            let serviceIds = Object.keys(shippingMethods);

            for (let id of serviceIds) {
                if (shippingMethods[id].selected) {
                    result++;
                }
            }

            return result;
        }

        /**
         * Shipping method activate/deactivate success callback.
         *
         * @param response
         */
        function handleActivateError(response) {
            selectedId = null;

            if (response.message) {
                utilityService.showFlashMessage(response.message, 'danger');
            }

            utilityService.hideSpinner();
        }

        /**
         * Handles successful retrieval of shop shipping method count.
         *
         * @param response
         */
        function getShopShippingMethodsCountCallback(response) {
            if (typeof response.count === 'number' && response.count !== 0) {
                showDisableShopShippingMethodsModal();
            }

            utilityService.hideSpinner();
        }

        /**
         * Shows disable shipping methods modal.
         */
        function showDisableShopShippingMethodsModal() {
            templateService.getComponent('pl-disable-methods-modal-wrapper', extensionPoint).classList.remove('hidden');
        }

        /**
         * Hides disable shipping methods modal.
         */
        function hideDisableShopShippingMethodsModal() {
            templateService.getComponent('pl-disable-methods-modal-wrapper', extensionPoint).classList.add('hidden');
        }

        /**
         * Handles successfully disabling shop shipping methods.
         *
         * @param response
         */
        function methodsDisabledSuccessCallback(response) {
            hideDisableShopShippingMethodsModal();

            if (response && response.message) {
                utilityService.showFlashMessage(response.message, 'success');
            }

            utilityService.hideSpinner();
        }

        /**
         * Handles error during disabling of shop shipping methods.
         *
         * @param response
         */
        function methodsDisabledFailedCallback(response) {
            hideDisableShopShippingMethodsModal();

            if (response && response.message) {
                utilityService.showFlashMessage(response.message, 'danger');
            }

            utilityService.hideSpinner();
        }

        /**
         * Handles event when shipping method configuration is clicked.
         *
         * @param event
         */
        function handleShippingMethodConfigClicked(event) {
            let configTemplate = templateService.getTemplate('pl-shipping-method-configuration-template')[0];
            let methodId = parseInt(event.target.getAttribute('data-pl-shipping-method-id'));
            shippingMethodTemplates[methodId].after(configTemplate);
            configTemplate.setAttribute('id', 'pl-shipping-method-config-form');

            constructShippingMethodConfigForm(methodId, configTemplate);
            scrollConfigForm(methodId);

            utilityService.enableInputMask();
            utilityService.configureInputElements();
        }

        /**
         * Fills config template with concrete information such as title etc.
         * Also, attaches proper event handlers to actionable elements of a config form.
         *
         * @param {int} id
         * @param {Element} template
         */
        function constructShippingMethodConfigForm(id, template) {
            methodModel = utilityService.cloneObject(shippingMethods[id]);
            templateService.getComponent('pl-method-title-input', template).value = methodModel.name;

            if (configuration.hasTaxConfiguration
                && methodModel.taxClass !== null
                && classExists(methodModel.taxClass)
            ) {
                templateService.getComponent('pl-tax-selector', template).value = methodModel.taxClass;
            }

            if (methodModel.pricePolicy === PRICING_POLICY_FIXED_BY_WEIGHT) {
                displayFixedPricesSubForm(false, false, true, true, true);
            } else if (methodModel.pricePolicy === PRICING_POLICY_FIXED_BY_VALUE) {
                displayFixedPricesSubForm(false, false, true, true, false);
            } else if (methodModel.pricePolicy === PRICING_POLICY_PERCENT) {
                displayPercentForm(true);
            }

            templateService.getComponent('pl-shipping-method-config-cancel-btn', template).addEventListener(
                'click',
                handleShippingMethodCancelClicked,
                true
            );

            templateService.getComponent('pl-shipping-method-config-save-btn', template).addEventListener(
                'click',
                handleShippingMethodSaveClicked,
                true
            );

            templateService.getComponent('pl-method-title-input', template).addEventListener(
                'blur',
                handleMethodNameChanged,
                true
            );

            if (
                typeof configuration.canDisplayCarrierLogos === 'undefined'
                || configuration.canDisplayCarrierLogos === true
            ) {
                let showLogoCheckbox = templateService.getComponent('pl-show-logo', template);
                showLogoCheckbox.addEventListener('click', handleShowLogoChanged, true);
                showLogoCheckbox.checked = methodModel.showLogo;
            }

            let pricingPolicySelector = templateService.getComponent('pl-pricing-policy-selector', template);
            pricingPolicySelector.addEventListener('change', handleShippingMethodPricingPolicyChanged, true);
            pricingPolicySelector.value = methodModel.pricePolicy;

            if (configuration.hasCountryConfiguration) {
                initializeCountryCountrySelector(methodModel, template);
            }
        }

        /**
         * Initializes shipping country selector
         *
         * @param {object} method
         * @param {Element} template
         */
        function initializeCountryCountrySelector(method, template) {
            let checkbox = templateService.getComponent('pl-country-selector-checkbox', template);
            let countryListBtn = templateService.getComponent('pl-country-list-btn', template);
            let countrySelectorCtrl = new Packlink.CountrySelectorController();

            checkbox.addEventListener('change', onChangeCountrySelectorCheckbox);
            countryListBtn.addEventListener('click', onClickCountryList);

            countrySelector.isShipToAllCountries = method.isShipToAllCountries;
            countrySelector.shippingCountries = method.shippingCountries;

            checkbox.checked = countrySelector.isShipToAllCountries;
            if (countrySelector.isShipToAllCountries) {
                countryListBtn.classList.add('hidden');
            }

            function onChangeCountrySelectorCheckbox(event) {
                countrySelector.isShipToAllCountries = event.target.checked;

                if (countrySelector.isShipToAllCountries) {
                    countryListBtn.classList.add('hidden');
                    countrySelectorCtrl.destroy();
                } else {
                    countryListBtn.classList.remove('hidden');
                    countrySelectorCtrl.display(availableCountries, countrySelector.shippingCountries, save, cancel);
                }
            }

            function onClickCountryList() {
                countrySelectorCtrl.display(availableCountries, countrySelector.shippingCountries, save, cancel);
            }

            function save(selectedCountries) {
                countrySelector.shippingCountries = selectedCountries;
                countrySelectorCtrl.destroy();
            }

            function cancel() {
                countrySelectorCtrl.destroy();
            }
        }

        /**
         * Scrolls config from.
         *
         * @param {number} methodId
         */
        function scrollConfigForm(methodId) {
            let scroller = templateService.getComponent('pl-table-scroll', extensionPoint);

            if (scroller) {
                let rowIndex = renderedShippingMethods.indexOf(methodId + '');
                scroller.scrollTo({
                    smooth: true,
                    left: 0,
                    top: (configuration.rowHeight * rowIndex + configuration.scrollOffset)
                });
            } else {
                shippingMethodTemplates[methodId].scrollIntoView();
            }
        }

        /**
         * Handles event when show logo checkbox is checked.
         *
         * @param event
         */
        function handleShowLogoChanged(event) {
            methodModel.showLogo = event.target.checked;
        }

        /**
         * Handles event when shipping method name is changed.
         *
         * @param event
         */
        function handleMethodNameChanged(event) {
            let value = event.target.value;
            if (value === '') {
                templateService.setError(event.target, Packlink.errorMsgs.required);
            } else if (configuration.maxTitleLength && value.length > configuration.maxTitleLength) {
                templateService.setError(event.target, Packlink.errorMsgs.titleLength);
            } else {
                templateService.removeError(event.target);
            }

            methodModel.name = value;
        }

        /**
         * Handles shipping method configuration cancel button clicked event.
         *
         * @param event
         */
        function handleShippingMethodCancelClicked(event) {
            closeConfigForm();
        }

        /**
         * Closes shipping method config form.
         */
        function closeConfigForm() {
            let configTemplate = templateService.getComponent(
                'pl-shipping-method-config-form',
                tableRowExtensionPoint
            );

            configTemplate.remove();

            if (methodModel.id) {
                scrollConfigForm(methodModel.id + '');
            }

            methodModel = {};
            utilityService.disableInputMask();
        }

        /**
         * Handles shipping method save clicked.
         *
         * @param event
         */
        function handleShippingMethodSaveClicked(event) {
            // Delete unnecessary fields in model.
            delete methodModel.deliveryType;
            delete methodModel.parcelDestination;
            delete methodModel.parcelOrigin;
            delete methodModel.selected;
            delete methodModel.logoUrl;
            delete methodModel.title;

            if (isShippingMethodValid()) {
                utilityService.showSpinner();

                if (methodModel.pricePolicy === PRICING_POLICY_PACKLINK) {
                    delete methodModel.fixedPriceByWeightPolicy;
                    delete methodModel.fixedPriceByValuePolicy;
                    delete methodModel.percentPricePolicy;
                } else if (methodModel.pricePolicy === PRICING_POLICY_PERCENT) {
                    delete methodModel.fixedPriceByWeightPolicy;
                    delete methodModel.fixedPriceByValuePolicy;
                } else if (methodModel.pricePolicy === PRICING_POLICY_FIXED_BY_VALUE) {
                    delete methodModel.percentPricePolicy;
                    delete methodModel.fixedPriceByWeightPolicy;
                } else {
                    delete methodModel.percentPricePolicy;
                    delete methodModel.fixedPriceByValuePolicy;
                }

                if (configuration.hasTaxConfiguration) {
                    methodModel.taxClass = templateService.getComponent('pl-tax-selector', extensionPoint).value;
                }

                if (configuration.hasCountryConfiguration) {
                    methodModel.isShipToAllCountries = countrySelector.isShipToAllCountries;

                    if (!methodModel.isShipToAllCountries) {
                        methodModel.shippingCountries = countrySelector.shippingCountries;
                    } else {
                        methodModel.shippingCountries = [];
                    }

                    countrySelector = {};
                }

                ajaxService.post(
                    configuration.saveUrl,
                    methodModel,
                    function (response) {
                        shippingMethods[response.id] = response;
                        utilityService.showFlashMessage(Packlink.successMsgs.shippingMethodSaved, 'success');
                        closeConfigForm();
                        renderShippingMethods();
                        if (shouldGetShopShippingMethodCount()) {
                            getShopShippingMethodCount();
                        } else {
                            utilityService.hideSpinner();
                        }
                    },
                    function (response) {
                        if (response.message) {
                            utilityService.showFlashMessage(response.message, 'danger');
                        }

                        utilityService.hideSpinner();
                    }
                );
            }
        }

        /**
         * Returns whether a call for getting number of shop shipping methods should be made.
         *
         * @return {boolean}
         */
        function shouldGetShopShippingMethodCount() {
            return getNumberOfActiveServices() === 1
                && typeof (configuration.getShopShippingMethodCountUrl) !== 'undefined';
        }

        /**
         * Performs an AJAX call for getting number of shop shipping methods, if proper conditions are met.
         */
        function getShopShippingMethodCount() {
            ajaxService.get(
                configuration.getShopShippingMethodCountUrl,
                getShopShippingMethodsCountCallback
            );
        }

        /**
         * Validates shipping method when save button in config form is clicked.
         *
         * @return {boolean}
         */
        function isShippingMethodValid() {
            let isValid = true;

            if (methodModel.name === '') {
                let nameInput = templateService.getComponent('pl-method-title-input', tableExtensionPoint);
                templateService.setError(nameInput, Packlink.errorMsgs.required);
                isValid = false;
            }

            if (configuration.maxTitleLength && methodModel.name.length > configuration.maxTitleLength) {
                let nameInput = templateService.getComponent('pl-method-title-input', tableExtensionPoint);
                templateService.setError(nameInput, Packlink.errorMsgs.titleLength);
                isValid = false;
            }

            if (methodModel.pricePolicy === PRICING_POLICY_PERCENT) {
                if (!methodModel.percentPricePolicy
                    || !methodModel.percentPricePolicy.amount
                    || typeof methodModel.percentPricePolicy.amount !== 'number'
                    || methodModel.percentPricePolicy.amount <= 0
                    || !methodModel.percentPricePolicy.increase && (methodModel.percentPricePolicy.amount >= 100)
                ) {
                    isValid = false;
                    let amountInput = templateService.getComponent('pl-perecent-amount', tableExtensionPoint);
                    templateService.setError(amountInput, Packlink.errorMsgs.invalid);
                }
            }

            if ((methodModel.pricePolicy === PRICING_POLICY_FIXED_BY_WEIGHT && !isFixedPriceValid(true, true, true))
                || (methodModel.pricePolicy === PRICING_POLICY_FIXED_BY_VALUE && !isFixedPriceValid(true, true, false))
            ) {
                isValid = false;
            }

            if (configuration.hasCountryConfiguration
                && !countrySelector.isShipToAllCountries
                && countrySelector.shippingCountries.length === 0) {
                utilityService.showFlashMessage(Packlink.errorMsgs.invalidCountryList, 'danger');

                isValid = false;
            }

            return isValid;
        }

        /**
         * Handles shipping method pricing policy changed event.
         *
         * @param event
         */
        function handleShippingMethodPricingPolicyChanged(event) {
            let pricingPolicy = parseInt(event.target.value);

            if (pricingPolicy === PRICING_POLICY_PACKLINK) {
                methodModel.pricePolicy = PRICING_POLICY_PACKLINK;
                templateService.setTemplate('', 'pl-pricing-extension-point');
            } else if (pricingPolicy === PRICING_POLICY_FIXED_BY_WEIGHT) {
                displayFixedPricesSubForm(false, false, true, true, true);
            } else if (pricingPolicy === PRICING_POLICY_FIXED_BY_VALUE) {
                displayFixedPricesSubForm(false, false, true, true, false);
            } else if (pricingPolicy === PRICING_POLICY_PERCENT) {
                displayPercentForm(true);
            }
        }

        /**
         * Displays fixed prices sub-form.
         *
         * @param {boolean} validateLastAmount
         * @param {boolean} validateLastTo
         * @param {boolean} rerenderForm
         * @param {boolean} shouldFocus
         * @param {boolean} byWeight
         */
        function displayFixedPricesSubForm(validateLastAmount, validateLastTo, rerenderForm, shouldFocus, byWeight) {
            let policy = getFixedPricePolicy(byWeight);
            if (rerenderForm) {
                let point = templateService.setTemplate(
                    byWeight ? 'pl-fixed-prices-by-weight-template' : 'pl-fixed-prices-by-value-template',
                    'pl-pricing-extension-point'
                );

                let addButton = templateService.getComponent('pl-fixed-price-add', point);
                addButton.addEventListener(
                    'click',
                    byWeight ? addFixedPriceByWeightCriteria : addFixedPriceByValueCriteria,
                    true
                );

                methodModel.pricePolicy = byWeight ? PRICING_POLICY_FIXED_BY_WEIGHT : PRICING_POLICY_FIXED_BY_VALUE;
                if (!methodModel[policy] || methodModel[policy].length === 0) {
                    methodModel[policy] = [];
                    methodModel[policy].push({from: 0, to: '', amount: ''});
                }

                let addedPricePoint = templateService.getComponent('pl-fixed-price-criteria-extension-point', point);
                for (let i = 0; i < methodModel[policy].length; i++) {
                    constructFixedPrice(methodModel[policy], i, addedPricePoint, byWeight);

                    if (shouldFocus && (i === methodModel[policy].length - 1)) {
                        templateService.getComponent('data-pl-to-id', extensionPoint, i).focus();
                    }
                }
            }

            isFixedPriceValid(validateLastAmount, validateLastTo, byWeight);

            utilityService.configureInputElements();
        }

        /**
         * Handles fixed price criteria added event.
         *
         * @param event
         */
        function addFixedPriceByWeightCriteria(event) {
            addFixedPriceCriteria(event, true);
        }

        /**
         * Handles fixed price criteria added event.
         *
         * @param event
         */
        function addFixedPriceByValueCriteria(event) {
            addFixedPriceCriteria(event, false);
        }

        /**
         * Handles fixed price criteria added event.
         *
         * @param event
         * @param {boolean} byWeight
         */
        function addFixedPriceCriteria(event, byWeight) {
            let policy = getFixedPricePolicy(byWeight);
            let index = methodModel[policy].length - 1;
            let currentCriteria = methodModel[policy][index];

            if (currentCriteria.to
                && typeof currentCriteria.to === 'number'
                && currentCriteria.to > currentCriteria.from
                && currentCriteria.amount
                && typeof currentCriteria.amount === 'number'
                && currentCriteria.amount > 0
            ) {
                methodModel[policy].push({from: currentCriteria.to, to: '', amount: ''});
                displayFixedPricesSubForm(false, false, true, true, byWeight);

                return;
            }

            displayFixedPricesSubForm(true, true, false, false, byWeight);
        }

        /**
         * Fills already added fixed price policy.
         * Attaches event handler to remove button.
         *
         * @param {object} policies
         * @param {int} id
         * @param {Element} point
         * @param {boolean} byWeight
         */
        function constructFixedPrice(policies, id, point, byWeight) {
            let template = templateService.getTemplate(
                byWeight ? 'pl-fixed-price-by-weight-criteria-template' : 'pl-fixed-price-by-value-criteria-template'
            )[0];

            template.setAttribute('data-pl-row', id.toString());

            if (policies.length === 1) {
                template.classList.add('first');
            } else {
                template.classList.remove('first');
            }

            initializeCriteriaFields(policies[id], id, template, byWeight);

            let removeBtn = templateService.getComponent('data-pl-remove', template, 'criteria');

            removeBtn.addEventListener(
                'click',
                byWeight ? handleFixedPriceByWeightCriteriaRemoved : handleFixedPriceByValueCriteriaRemoved,
                true
            );
            removeBtn.setAttribute('data-pl-criteria-id', id.toString());

            point.appendChild(template);
        }

        /**
         * Fills criteria fields.
         *
         * @param {object} policy
         * @param {int} id
         * @param {Element} template
         * @param {boolean} byWeight
         */
        function initializeCriteriaFields(policy, id, template, byWeight) {
            let fields = [
                'from',
                'to',
                'amount'
            ];

            for (let field of fields) {
                let input = templateService.getComponent('data-pl-fixed-price', template, field);
                input.value = policy[field];
                input.setAttribute('data-pl-' + field + '-id', id.toString());

                if (field === 'from') {
                    if (id === 0) {
                        input.addEventListener('blur', function (event) {
                            onFixedPriceFromBlur(event, byWeight);
                        }, true);
                    } else {
                        input.disabled = true;
                    }
                }

                if (field === 'to') {
                    input.addEventListener(
                        'blur',
                        byWeight ? onFixedPriceByWeightToBlur : onFixedPriceByValueToBlur, true
                    );
                    input.setAttribute('tabindex', id * 2 + 1);
                }

                if (field === 'amount') {
                    input.addEventListener(
                        'blur',
                        byWeight ? onFixedPriceByWeightAmountBlur : onFixedPriceByValueAmountBlur,
                        true
                    );
                    input.setAttribute('tabindex', id * 2 + 2);
                }
            }
        }

        /**
         * Handles on Fixed Price From input field blur event.
         *
         * @param event
         * @param {boolean} byWeight
         */
        function onFixedPriceFromBlur(event, byWeight) {
            let policy = getFixedPricePolicy(byWeight);
            let index = parseInt(event.target.getAttribute('data-pl-from-id'));

            if (index !== 0) {
                return;
            }

            let value = event.target.value;
            let numericValue = parseFloat(value);
            // noinspection EqualityComparisonWithCoercionJS
            methodModel[policy][index].from = event.target.value == numericValue ? numericValue : value;

            templateService.removeError(event.target);

            displayFixedPricesSubForm(index === methodModel[policy].length - 1, false, false, false, byWeight)
        }

        /**
         * Handles blur event on to input field.
         *
         * @param event
         */
        function onFixedPriceByWeightToBlur(event) {
            onFixedPriceToBlur(event, true);
        }

        /**
         * Handles blur event on to input field.
         *
         * @param event
         */
        function onFixedPriceByValueToBlur(event) {
            onFixedPriceToBlur(event, false);
        }

        /**
         * Handles blur event on to input field.
         *
         * @param event
         * @param {boolean} byWeight
         */
        function onFixedPriceToBlur(event, byWeight) {
            let policy = getFixedPricePolicy(byWeight);
            let index = parseInt(event.target.getAttribute('data-pl-to-id'));
            let value = event.target.value;
            let numericValue = parseFloat(value);
            // noinspection EqualityComparisonWithCoercionJS
            methodModel[policy][index].to = event.target.value == numericValue ? numericValue : value;

            if (value !== '' && !isNaN(value)) {
                if (index < methodModel[policy].length - 1) {
                    let successor = methodModel[policy][index + 1];
                    let isSuccessorLast = index + 1 === methodModel[policy].length - 1;

                    if (typeof successor.to === 'number'
                        && (successor.to > numericValue || isSuccessorLast)
                        || typeof successor.to !== 'number'
                    ) {
                        successor.from = methodModel[policy][index].to;
                        let fromInput = templateService.getComponent('data-pl-from-id', extensionPoint, index + 1);
                        fromInput.value = successor.from;
                        if ((isSuccessorLast) && typeof successor.to === 'number' && successor.to <= successor.from) {
                            successor.to = '';
                        }
                    }
                }
            }

            templateService.removeError(event.target);
            displayFixedPricesSubForm(false, index === methodModel[policy].length - 1, false, false, byWeight);
        }

        /**
         * Handles fixed price criteria amount blur event.
         *
         * @param event
         */
        function onFixedPriceByWeightAmountBlur(event) {
            onFixedPriceAmountBlur(event, true);
        }

        /**
         * Handles fixed price criteria amount blur event.
         *
         * @param event
         */
        function onFixedPriceByValueAmountBlur(event) {
            onFixedPriceAmountBlur(event, false);
        }

        /**
         * Handles fixed price criteria amount blur event.
         *
         * @param event
         * @param {boolean} byWeight
         */
        function onFixedPriceAmountBlur(event, byWeight) {
            let policy = getFixedPricePolicy(byWeight);
            let index = parseInt(event.target.getAttribute('data-pl-amount-id'));
            let numeric = parseFloat(event.target.value);

            // noinspection EqualityComparisonWithCoercionJS
            methodModel[policy][index].amount = event.target.value == numeric ? numeric : event.target.value;
            templateService.removeError(event.target);
            displayFixedPricesSubForm(index === methodModel[policy].length - 1, false, false, false, byWeight);
        }

        /**
         * Validates fixed price criteria.
         *
         * @param validateLastAmount
         * @param validateLastTo
         * @param {boolean} byWeight
         *
         * @return {boolean}
         */
        function isFixedPriceValid(validateLastAmount, validateLastTo, byWeight) {
            if (!isFixedPriceRangeValid(byWeight)
                || !isFixedPriceInputTypeValid(byWeight)
                || !isFixedPriceAmountValid(byWeight)
                || !isFixedPriceNumberOfDecimalPlacesValid(byWeight)
            ) {
                return false;
            }

            if (validateLastAmount || validateLastTo) {
                let result = true;
                let policies = methodModel[getFixedPricePolicy(byWeight)];
                let index = policies.length - 1;
                let last = policies[index];

                if (validateLastTo) {
                    result = validateFixedPriceField('to', last, index, last.from);
                }

                if (validateLastAmount) {
                    result = validateFixedPriceField('amount', last, index, byWeight ? 0.001 : 0);
                }

                return result;
            }

            return true;
        }

        function validateFixedPriceField(fieldName, last, index, lowerBound) {
            let input = templateService.getComponent('data-pl-' + fieldName + '-id', tableExtensionPoint, index);
            let result = true;
            // noinspection EqualityComparisonWithCoercionJS
            if (last[fieldName] === ''
                || isNaN(last[fieldName])
                || typeof last[fieldName] !== 'number'
                || last[fieldName] < lowerBound
                || last[fieldName] != parseFloat(last[fieldName].toFixed(2))
            ) {
                result = false;
                templateService.setError(input, Packlink.errorMsgs.invalid);
            } else {
                templateService.removeError(input);
            }

            return result;
        }

        /**
         * Validates fixed price input type.
         */
        function isFixedPriceInputTypeValid(byWeight) {
            let policies = methodModel[getFixedPricePolicy(byWeight)];
            let fields = ['from', 'amount', 'to'];
            let result = true;

            for (let i = 0; i < policies.length; i++) {
                for (let field of fields) {
                    let value = policies[i][field];
                    if (value === '' || isNaN(value) || typeof value !== 'number') {
                        let input = templateService.getComponent('data-pl-' + field + '-id', tableExtensionPoint, i);
                        templateService.setError(input, Packlink.errorMsgs.numeric);
                        result = false;
                    }
                }
            }

            return result;
        }

        /**
         * Validates fixed price amount.
         *
         * @return {boolean}
         */
        function isFixedPriceAmountValid(byWeight) {
            let result = true;
            let boundary = byWeight ? 0.0001 : 0;
            let policies = methodModel[getFixedPricePolicy(byWeight)];

            for (let i = 0; i < policies.length; i++) {
                if (policies[i]['amount'] < boundary) {
                    let input = templateService.getComponent('data-pl-amount-id', tableExtensionPoint, i);
                    templateService.setError(input, Packlink.errorMsgs.invalid);
                    result = false;
                }
            }

            return result;
        }

        /**
         * Validates fixed price range.
         *
         * @return {boolean}
         */
        function isFixedPriceRangeValid(byWeight) {
            let policies = methodModel[getFixedPricePolicy(byWeight)];
            let result = true;

            for (let i = 0; i < policies.length; i++) {
                let current = policies[i];
                let successor = policies.length < i + 1 ? policies[i + 1] : null;

                if (current.from < 0) {
                    let input = templateService.getComponent('data-pl-from-id', tableExtensionPoint, i);
                    templateService.setError(input, Packlink.errorMsgs.invalid);
                    result = false;
                }

                if (current.from >= current.to || (successor && successor.from && current.to > successor.from)) {
                    let input = templateService.getComponent('data-pl-to-id', tableExtensionPoint, i);
                    templateService.setError(input, Packlink.errorMsgs.invalid);
                    result = false;
                }
            }

            return result;
        }

        /**
         * Validates fixed price number of decimal places.
         */
        function isFixedPriceNumberOfDecimalPlacesValid(byWeight) {
            let policies = methodModel[getFixedPricePolicy(byWeight)];
            let result = true;

            for (let i = 0; i < policies.length; i++) {
                let current = policies[i];
                // noinspection EqualityComparisonWithCoercionJS
                if (current.to && current.to != current.to.toFixed(2)) {
                    let input = templateService.getComponent('data-pl-to-id', tableExtensionPoint, i);
                    templateService.setError(input, Packlink.errorMsgs.numberOfDecimalPlaces);
                    result = false;
                }

                // noinspection EqualityComparisonWithCoercionJS
                if (current.amount && current.amount != current.amount.toFixed(2)) {
                    let input = templateService.getComponent('data-pl-amount-id', tableExtensionPoint, i);
                    templateService.setError(input, Packlink.errorMsgs.numberOfDecimalPlaces);
                    result = false;
                }
            }

            return result;
        }

        /**
         * Handles removal of fixed price criteria.
         *
         * @param event
         */
        function handleFixedPriceByWeightCriteriaRemoved(event) {
            handleFixedPriceCriteriaRemoved(event, true);
        }

        /**
         * Handles removal of fixed price criteria.
         *
         * @param event
         */
        function handleFixedPriceByValueCriteriaRemoved(event) {
            handleFixedPriceCriteriaRemoved(event, false);
        }

        /**
         * Handles removal of fixed price criteria.
         *
         * @param event
         * @param {boolean} byWeight
         */
        function handleFixedPriceCriteriaRemoved(event, byWeight) {
            let policies = methodModel[getFixedPricePolicy(byWeight)];
            let index = parseInt(event.target.getAttribute('data-pl-criteria-id'));
            if (index !== policies.length - 1) {
                policies[index + 1].from = policies[index].from;
            }

            policies.splice(index, 1);
            displayFixedPricesSubForm(false, false, true, true, byWeight);
        }

        function getFixedPricePolicy(byWeight) {
            return byWeight ? 'fixedPriceByWeightPolicy' : 'fixedPriceByValuePolicy';
        }

        /**
         * Displays packlink percent sub-form.
         *
         * @param {boolean} [shouldFocusInput]
         */
        function displayPercentForm(shouldFocusInput) {
            templateService.setTemplate('pl-packlink-percent-template', 'pl-pricing-extension-point');

            let buttons = templateService.getComponentsByAttribute(
                'data-pl-packlink-percent-btn',
                tableExtensionPoint
            );

            for (let button of buttons) {
                button.addEventListener('click', handlePacklinkPercentButtonClicked, true);
            }

            let input = templateService.getComponent(
                'pl-perecent-amount',
                tableExtensionPoint
            );
            input.addEventListener('blur', handlePercentInputBlurEvent, true);

            if (shouldFocusInput) {
                input.focus();
            }

            methodModel.pricePolicy = PRICING_POLICY_PERCENT;

            if (!methodModel.percentPricePolicy) {
                methodModel.percentPricePolicy = {
                    increase: true
                };
            } else {
                if (methodModel.percentPricePolicy.amount) {
                    input.value = methodModel.percentPricePolicy.amount;

                    if (!methodModel.percentPricePolicy.increase) {
                        if (methodModel.percentPricePolicy.amount >= 100) {
                            templateService.setError(input, Packlink.errorMsgs.invalid);
                        }
                    }
                }
            }

            setPercentButtonsClass();
            utilityService.configureInputElements();
        }

        /**
         * Handles blur event on percent input field;
         *
         * @param event
         */
        function handlePercentInputBlurEvent(event) {
            let value = event.target.value;
            let numeric = parseFloat(value);
            // noinspection EqualityComparisonWithCoercionJS
            methodModel.percentPricePolicy.amount = value == numeric ? numeric : value;

            if (value === ''
                || isNaN(value)
                || value <= 0
                || !methodModel.percentPricePolicy.increase && (value >= 100)
            ) {
                templateService.setError(event.target, Packlink.errorMsgs.invalid);
            } else {
                displayPercentForm();
            }
        }

        /**
         * Sets correct class to switch buttons.
         */
        function setPercentButtonsClass() {
            let increaseButton = templateService.getComponent(
                'data-pl-packlink-percent-btn',
                tableExtensionPoint,
                'increase'
            );

            let decreaseButton = templateService.getComponent(
                'data-pl-packlink-percent-btn',
                tableExtensionPoint,
                'decrease'
            );

            if (methodModel.percentPricePolicy.increase) {
                increaseButton.classList.add('selected');
                decreaseButton.classList.remove('selected');
            } else {
                increaseButton.classList.remove('selected');
                decreaseButton.classList.add('selected');
            }
        }

        /**
         * Handles event when increase/decrease price by percent button is clicked.
         *
         * @param event
         */
        function handlePacklinkPercentButtonClicked(event) {
            let value = event.target.getAttribute('data-pl-packlink-percent-btn');
            methodModel.percentPricePolicy.increase = value === 'increase';
            displayPercentForm(true);
        }

        /**
         * Fills tax selector.
         *
         * @param response
         */
        function getTaxClassesSuccessHandler(response) {
            taxSelector = templateService.getComponent('pl-tax-selector', document.body);

            while (taxSelector.firstChild) {
                taxSelector.firstChild.remove();
            }

            for (let taxClass of response) {
                let option = document.createElement('option');
                option.value = taxClass['value'];
                option.innerHTML = taxClass['label'];
                taxSelector.appendChild(option);
                taxClasses.push(taxClass['value']);
            }

            taxSelector.value = response[0]['value'];

            if (spinnerBarrier === ++spinnerBarrierCount) {
                utilityService.hideSpinner();
            }
        }

        function getShippingCountriesHandler(response) {
            availableCountries = response;

            if (spinnerBarrier === ++spinnerBarrierCount) {
                utilityService.hideSpinner();
            }
        }

        /**
         * Checks whether tax class exists in system.
         *
         * @param {string} taxClass
         *
         * @return {boolean}
         */
        function classExists(taxClass) {
            for (let taxClassValue of taxClasses) {
                if (taxClassValue === taxClass) {
                    return true;
                }
            }

            return false;
        }

        /**
         * DASHBOARD MODAL SECTION
         */

        /**
         * Initializes dashboard steps.
         */
        function initSteps() {
            initParcelStep();
            initWarehouseStep();
            initMethodsStep();
            initStepSubtitle();
        }

        /**
         * Initializes default parcel step.
         */
        function initParcelStep() {
            let status = dashboardData.parcelSet ? 'completed' : 'in-progress';
            let step = templateService.getComponent('pl-parcel-step');
            if (status !== 'disabled') {
                step.addEventListener('click', handleParcelStepClicked, true);
            } else {
                step.removeEventListener('click', handleParcelStepClicked);
            }

            step.classList.remove(...STATUSES);
            step.classList.add(status);
        }

        /**
         * Initializes step subtitle.
         */
        function initStepSubtitle() {
            if (!dashboardData.parcelSet || !dashboardData.warehouseSet) {
                templateService.getComponent('pl-step-subtitle').remove();
            }
        }

        /**
         * Initializes warehouse step.
         */
        function initWarehouseStep() {
            let status = 'completed';

            if (!dashboardData.warehouseSet) {
                if (dashboardData.parcelSet) {
                    status = 'in-progress';
                } else {
                    status = 'disabled';
                }
            }

            let step = templateService.getComponent('pl-warehouse-step');
            step.classList.remove(...STATUSES);
            step.classList.add(status);

            if (status !== 'disabled') {
                step.addEventListener('click', handleWarehouseStepClicked, true);
            } else {
                step.removeEventListener('click', handleWarehouseStepClicked);
            }
        }

        /**
         * Initializes shipping methods step.
         */
        function initMethodsStep() {
            let status = 'completed';

            if (!dashboardData.shippingMethodSet) {
                if (dashboardData.parcelSet && dashboardData.warehouseSet) {
                    status = 'in-progress';
                } else {
                    status = 'disabled';
                }
            }

            let step = templateService.getComponent('pl-shipping-methods-step');
            step.classList.remove(...STATUSES);
            step.classList.add(status);

            if (status !== 'disabled') {
                step.addEventListener('click', handleMethodsStepClicked, true);
            } else {
                step.removeEventListener('click', handleMethodsStepClicked);
            }
        }

        /**
         * Handles clicked event on parcel step.
         *
         * @param event
         */
        function handleParcelStepClicked(event) {
            state.startStep('default-parcel');
        }

        /**
         * Handles clicked event on warehouse step.
         *
         * @param event
         */
        function handleWarehouseStepClicked(event) {
            state.startStep('default-warehouse');
        }

        /**
         * Handles clicked event on shipping methods step.
         *
         * @param event
         */
        function handleMethodsStepClicked(event) {
            hideDashboardModal();
        }

        /**
         * Shows dashboard modal.
         */
        function showDashboardModal() {
            templateService.getComponent('pl-dashboard-modal-wrapper', extensionPoint).classList.remove('hidden');
            utilityService.hideSpinner();
            hideGettingShippingMethodsMessage();
            isDashboardShown = true;
        }

        /**
         * Hides dashboard modal.
         *
         * @param {boolean} [isAutoconfigure]
         */
        function hideDashboardModal(isAutoconfigure) {
            if (typeof isAutoconfigure !== 'boolean') {
                isAutoconfigure = false;
            }

            if (!isAutoconfigure && (!dashboardData.parcelSet || !dashboardData.warehouseSet)) {
                return;
            }

            templateService.getComponent('pl-dashboard-modal-wrapper', extensionPoint).classList.add('hidden');
            if (Object.keys(shippingMethods).length === 0) {
                showGettingShippingMethodsMessage();
            }

            isDashboardShown = false;
        }

        /**
         * Retrieves spinner barrier value. Spinner barrier is used to denote number of required ajax requests that
         * have to be completed before initial loading spinner is hidden.
         *
         * @return {number}
         */
        function getSpinnerBarrier() {
            return 1 + !!(configuration.hasTaxConfiguration) + !!(configuration.hasCountryConfiguration);
        }
    }

    Packlink.ShippingMethodsController = ShippingMethodsController;
})();
