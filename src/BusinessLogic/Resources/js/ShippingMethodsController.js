var Packlink = window.Packlink || {};

(function () {
    function ShippingMethodsController(configuration) {
        const PRICING_POLICY_PACKLINK = 1;
        const PRICING_POLICY_PERCENT = 2;
        const PRICING_POLICY_FIXED = 3;

        const STATUSES = [
            'disabled',
            'in-progress',
            'completed',
        ];

        let templateService = Packlink.templateService;
        let utilityService = Packlink.utilityService;
        let ajaxService = Packlink.ajaxService;
        let state = Packlink.state;

        let isDashboardShowed = false;

        let selectedId = null;

        let currentNavTab = 'all';

        let spinnerBarrierCount = 0;
        let spinnerBarrier = configuration.hasTaxConfiguration ? 2 : 1;

        let dashboardData = {};
        let methodModel = {};
        let taxClasses = [];

        let filters = {
            title: [],
            deliveryType: [],
            parcelOrigin: [],
            parcelDestination: [],
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

        //Register public methods and variables.
        this.display = display;

        /**
         * Displays page content.
         */
        function display() {
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

            templateService.getComponent('pl-delete-methods-modal-wrapper').addEventListener(
                'click',
                hideDeleteShopShippingMethodsModal
            );

            templateService.getComponent('pl-delete-methods-modal-cancel').addEventListener(
                'click',
                hideDeleteShopShippingMethodsModal
            );

            templateService.getComponent('pl-delete-methods-modal-accept').addEventListener(
                'click',
                function () {
                    utilityService.showSpinner();
                    ajaxService.get(
                        configuration.deleteShopShippingMethodsUrl,
                        methodsDeletedSuccessCallback,
                        methodsDeletedFailedCallback
                    );
                }
            );

            if (configuration.hasTaxConfiguration) {
                ajaxService.get(configuration.getTaxClassesUrl, getTaxClassesSuccessHandler);
            }

            ajaxService.get(configuration.getStatusUrl, getStatusHandler);
            ajaxService.get(configuration.getAllUrl, getShippingMethodsHandler);
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
                if (!isDashboardShowed) {
                    showNoShippingMethodsMessage();
                }

                setTimeout(function () {
                    ajaxService.get(configuration.getAllUrl, getShippingMethodsHandler)
                }, 1000);
            } else {
                hideNoShippingMethodsMessage();
            }


            renderShippingMethods();

            if (spinnerBarrier === spinnerBarrierCount) {
                utilityService.hideSpinner();
            } else {
                spinnerBarrierCount++;
            }
        }

        /**
         * Shows message when no shipping service is available.
         */
        function showNoShippingMethodsMessage() {
            utilityService.enableInputMask();
            templateService.getComponent('pl-no-shipping-services', extensionPoint).classList.remove('hidden');
        }

        /**
         * Hides message when shipping services are availbale.
         */
        function hideNoShippingMethodsMessage() {
            utilityService.disableInputMask();
            templateService.getComponent('pl-no-shipping-services', extensionPoint).classList.add('hidden');
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

            if (spinnerBarrier === spinnerBarrierCount) {
                utilityService.hideSpinner();
            } else {
                spinnerBarrierCount++;
            }
        }

        /**
         * Adds static component to shipping methods page.
         *
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
                renderedShippingMethods = renderedShippingMethods.filter(function (item) {
                    return result[filterTypes[i]].indexOf(item) !== -1;
                })
            }

            // Take only unique values. Rendered shipping methods variable has to behave like a set.
            // Also take current selection tab into consideration.
            renderedShippingMethods = renderedShippingMethods.filter(function (r, index, set) {
                let shippingMethod = shippingMethods[r];
                let selectRequired = currentNavTab === 'selected';
                return (set.indexOf(r) === index) && (selectRequired && shippingMethod.selected || !selectRequired);
            });
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
            selectButton.setAttribute('data-pl-shipping-method-id', id);
            selectButton.addEventListener('click', handleShippingMethodSelectClicked, true);

            templateService.getComponent('pl-logo', template).setAttribute('src', shippingMethod.logoUrl);

            if (shippingMethod.selected) {
                selectButton.classList.add('selected');
            }

            let configButton = templateService.getComponent('pl-shipping-method-config-btn', template);
            configButton.setAttribute('data-pl-shipping-method-id', id);
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
            } else if (shippingMethod.pricePolicy === PRICING_POLICY_FIXED) {
                indicator = 'fixed';
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
            selectedId = null;

            if (response.message) {
                utilityService.showFlashMessage(response.message, 'success');
            }

            if (!dashboardData.shippingMethodSet) {
                ajaxService.get(configuration.getShopShippingMethodCountUrl, getShopShippingMethodsCountCallback);
            } else {
                utilityService.hideSpinner();
            }
            renderShippingMethods();
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
                showDeleteShopShippingMethodsModal();
            }

            utilityService.hideSpinner();
        }

        /**
         * Shows delete shipping methods modal.
         */
        function showDeleteShopShippingMethodsModal() {
            templateService.getComponent('pl-delete-methods-modal-wrapper', extensionPoint).classList.remove('hidden');
        }

        /**
         * Hides delete shipping methods modal.
         */
        function hideDeleteShopShippingMethodsModal() {
            templateService.getComponent('pl-delete-methods-modal-wrapper', extensionPoint).classList.add('hidden');
        }

        /**
         * Handles successfully deleting shop shipping methods.
         *
         * @param response
         */
        function methodsDeletedSuccessCallback(response) {
            hideDeleteShopShippingMethodsModal();

            if (response && response.message) {
                utilityService.showFlashMessage(response.message, 'success');
            }

            utilityService.hideSpinner();
        }

        /**
         * Handles error during deletion of shop shipping methods.
         *
         * @param response
         */
        function methodsDeletedFailedCallback(response) {
            hideDeleteShopShippingMethodsModal();

            if (response && response.message) {
                utilityService.showFlashMessage(response.message, 'error');
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
            let methodId = event.target.getAttribute('data-pl-shipping-method-id');
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

            if (
                configuration.hasTaxConfiguration
                && methodModel.taxClass !== null
                && classExists(methodModel.taxClass)
            ) {
                templateService.getComponent('pl-tax-selector', template).value = methodModel.taxClass;
            }

            if (methodModel.pricePolicy === PRICING_POLICY_FIXED) {
                displayFixedPricesSubform(false, false);
            }

            if (methodModel.pricePolicy === PRICING_POLICY_PERCENT) {
                displayPercentForm();
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

            let showLogoCheckbox = templateService.getComponent('pl-show-logo', template);
            showLogoCheckbox.addEventListener('click', handleShowLogoChanged, true);
            showLogoCheckbox.checked = methodModel.showLogo;

            let pricingPolicySelector = templateService.getComponent('pl-pricing-policy-selector', template);
            pricingPolicySelector.addEventListener('change', handleShippingMethodPricingPolicyChanged, true);
            pricingPolicySelector.value = methodModel.pricePolicy;
        }

        /**
         * Scrolls config from.
         *
         * @param {number} methodId
         */
        function scrollConfigForm(methodId) {
            let scroller = templateService.getComponent('pl-table-scroll', extensionPoint);

            if (scroller) {
                let rowIndex = renderedShippingMethods.indexOf(methodId);
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
                templateService.setError(event.target, Packlink.errorMsgs.titleLength)
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
                    delete methodModel.fixedPricePolicy;
                    delete methodModel.percentPricePolicy;
                } else if (methodModel.pricePolicy === PRICING_POLICY_PERCENT) {
                    delete methodModel.fixedPricePolicy;
                } else {
                    delete methodModel.percentPricePolicy;
                }

                if (configuration.hasTaxConfiguration) {
                    methodModel.taxClass = templateService.getComponent('pl-tax-selector', extensionPoint).value;
                }

                ajaxService.post(
                    configuration.saveUrl,
                    methodModel,
                    function (response) {
                        shippingMethods[response.id] = response;
                        utilityService.showFlashMessage(Packlink.successMsgs.shippingMethodSaved, 'success');
                        closeConfigForm();
                        renderShippingMethods();
                        utilityService.hideSpinner();
                    },
                    function (response) {
                        if (response.message) {
                            utilityService.showFlashMessage(response.message, 'danger');
                        }

                        closeConfigForm();

                        utilityService.hideSpinner();
                    }
                );
            }
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

            if (methodModel.pricePolicy === PRICING_POLICY_FIXED) {
                if (!isFixedPriceValid(true, true)) {
                    isValid = false;
                }
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
            }

            if (pricingPolicy === PRICING_POLICY_FIXED) {
                displayFixedPricesSubform(false, false);
            }

            if (pricingPolicy === PRICING_POLICY_PERCENT) {
                displayPercentForm();
            }
        }


        /**
         * Displays fixed prices subform.
         *
         * @param validateLastAmount
         * @param validateLastTo
         */
        function displayFixedPricesSubform(validateLastAmount, validateLastTo) {
            let point = templateService.setTemplate(
                'pl-fixed-prices-template',
                'pl-pricing-extension-point'
            );

            let addButton = templateService.getComponent('pl-fixed-price-add', point);
            addButton.addEventListener('click', addFixedPriceCriteria, true);

            methodModel.pricePolicy = PRICING_POLICY_FIXED;

            if (!methodModel.fixedPricePolicy || methodModel.fixedPricePolicy.length === 0) {
                methodModel.fixedPricePolicy = [];
                methodModel.fixedPricePolicy.push({from: 0, to: '', amount: ''});
            }

            let addedPricePoint = templateService.getComponent('pl-fixed-price-criteria-extension-point', point);
            for (let i = 0; i < methodModel.fixedPricePolicy.length; i++) {
                constructFixedPrice(methodModel.fixedPricePolicy[i], i, addedPricePoint);
            }

            isFixedPriceValid(validateLastAmount, validateLastTo);

            utilityService.configureInputElements();
        }

        /**
         * Handles fixed price criteria added event.
         *
         * @param event
         */
        function addFixedPriceCriteria(event) {
            let index = methodModel.fixedPricePolicy.length - 1;
            let currentCriteria = methodModel.fixedPricePolicy[index];

            if (
                currentCriteria.to &&
                typeof currentCriteria.to === 'number' &&
                currentCriteria.to > currentCriteria.from &&
                currentCriteria.amount &&
                typeof currentCriteria.amount === 'number' &&
                currentCriteria.amount > 0
            ) {
                methodModel.fixedPricePolicy.push({from: currentCriteria.to, to: '', amount: ''});
                displayFixedPricesSubform(false, false);
                return;
            }

            displayFixedPricesSubform(true, true);
        }

        /**
         * Fills already added fixed price policy.
         * Attaches event handler to remove button.
         *
         * @param {object} policy
         * @param {int} id
         * @param {Element} point
         */
        function constructFixedPrice(policy, id, point) {
            let template = templateService.getTemplate('pl-fixed-price-criteria-template')[0];

            template.setAttribute('data-pl-row', id);

            if (methodModel.fixedPricePolicy.length === 1) {
                template.classList.add('first');
            } else {
                template.classList.remove('first');
            }

            initializeCriteriaFields(policy, id, template);

            let removeBtn = templateService.getComponent('data-pl-remove', template, 'criteria');

            removeBtn.addEventListener('click', handleFixedPriceCriteriaRemoved, true);
            removeBtn.setAttribute('data-pl-criteria-id', id);

            point.appendChild(template);
        }

        /**
         * Fills criteria fields.
         *
         * @param {object} policy
         * @param {int} id
         * @param {Element} template
         */
        function initializeCriteriaFields(policy, id, template) {
            let fields = [
                'from',
                'to',
                'amount',
            ];

            for (let field of fields) {
                let input = templateService.getComponent('data-pl-fixed-price', template, field);
                input.value = policy[field];
                input.setAttribute(`data-pl-${field}-id`, id);

                if (field === 'to') {
                    input.addEventListener('blur', onFixedPriceToBlur, true);
                }

                if (field === 'amount') {
                    input.addEventListener('blur', onFixedPriceAmountBlur, true);
                }
            }
        }

        /**
         * Handles blur event on to input field.
         *
         * @param event
         */
        function onFixedPriceToBlur(event) {
            let index = parseInt(event.target.getAttribute('data-pl-to-id'));
            let value = event.target.value;
            let numericValue = parseFloat(value);
            methodModel.fixedPricePolicy[index].to = event.target.value == numericValue ? numericValue : value;

            if (value !== '' && !isNaN(value)) {
                if (index < methodModel.fixedPricePolicy.length - 1) {
                    let successor = methodModel.fixedPricePolicy[index + 1];
                    let isSuccessorLast = index + 1 === methodModel.fixedPricePolicy.length - 1;

                    if (
                        typeof successor.to === 'number' && (successor.to > numericValue || (isSuccessorLast)) ||
                        typeof successor.to !== 'number'
                    ) {
                        successor.from = methodModel.fixedPricePolicy[index].to;
                        if ((isSuccessorLast) && typeof successor.to === 'number' && successor.to <= successor.from) {
                            successor.to = '';
                        }
                    }
                }
            }

            displayFixedPricesSubform(false, index === methodModel.fixedPricePolicy.length - 1);
        }

        /**
         * Handles fixed price criteria amount blur event.
         *
         * @param event
         */
        function onFixedPriceAmountBlur(event) {
            let index = parseInt(event.target.getAttribute('data-pl-amount-id'));
            let numeric = parseFloat(event.target.value);
            methodModel.fixedPricePolicy[index].amount = event.target.value == numeric ? numeric : event.target.value;
            displayFixedPricesSubform(index === methodModel.fixedPricePolicy.length - 1, false);
        }

        /**
         * Validates fixed price criteria.
         *
         * @param validateLastAmount
         * @param validateLastTo
         *
         * @return {boolean}
         */
        function isFixedPriceValid(validateLastAmount, validateLastTo) {
            if (!isFixedPriceInputTypeValid()) {
                return false;
            }

            if (!isFixedPriceAmountValid()) {
                return false;
            }

            if (!isFixedPriceRangeValid()) {
                return false;
            }

            if (!isFixedPriceNumberOfDecimalPlacesValid()) {
                return false;
            }

            if (validateLastAmount || validateLastTo) {
                let result = true;
                let index = methodModel.fixedPricePolicy.length - 1;

                let last = methodModel.fixedPricePolicy[index];
                if (validateLastTo) {
                    if (last.to === '' || isNaN(last.to) || typeof last.to !== 'number' || last.to <= last.from ||
                        last.to != parseFloat(last.to.toFixed(2))) {
                        result = false;
                        let input = templateService.getComponent('data-pl-to-id', tableExtensionPoint, index);
                        templateService.setError(input, Packlink.errorMsgs.invalid);
                    }
                }

                if (validateLastAmount) {
                    if (last.amount === '' || isNaN(last.amount) || typeof last.amount !== 'number' || last.amount <= 0 ||
                        last.amount != parseFloat(last.amount.toFixed(2))) {
                        result = false;
                        let input = templateService.getComponent('data-pl-amount-id', tableExtensionPoint, index);
                        templateService.setError(input, Packlink.errorMsgs.invalid);
                    }
                }

                return result;
            }

            return true;
        }

        /**
         * Validates fixed price input type.
         */
        function isFixedPriceInputTypeValid() {
            let fields = ['amount', 'to'];
            let result = true;

            for (let i = 0; i < methodModel.fixedPricePolicy.length - 1; i++) {
                for (let field of fields) {
                    let value = methodModel.fixedPricePolicy[i][field];
                    if (value === '' || isNaN(value) || typeof value !== 'number') {
                        let input = templateService.getComponent(`data-pl-${field}-id`, tableExtensionPoint, i);
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
        function isFixedPriceAmountValid() {
            let result = true;

            for (let i = 0; i < methodModel.fixedPricePolicy.length - 1; i++) {
                if (methodModel.fixedPricePolicy[i]['amount'] <= 0) {
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
        function isFixedPriceRangeValid() {
            let result = true;
            for (let i = 0; i < methodModel.fixedPricePolicy.length - 1; i++) {
                let current = methodModel.fixedPricePolicy[i];
                let successor = methodModel.fixedPricePolicy[i + 1];
                if (current.from >= current.to || successor.from && current.to > successor.from) {
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
        function isFixedPriceNumberOfDecimalPlacesValid() {
            let result = true;

            for (let i = 0; i < methodModel.fixedPricePolicy.length - 1; i++) {
                let current = methodModel.fixedPricePolicy[i];
                if (current.to != current.to.toFixed(2)) {
                    let input = templateService.getComponent('data-pl-to-id', tableExtensionPoint, i);
                    templateService.setError(input, Packlink.errorMsgs.numberOfDecimalPlaces);
                    result = false;
                }

                if (current.amount != current.amount.toFixed(2)) {
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
        function handleFixedPriceCriteriaRemoved(event) {
            let index = parseInt(event.target.getAttribute('data-pl-criteria-id'));
            if (index !== methodModel.fixedPricePolicy.length - 1) {
                methodModel.fixedPricePolicy[index + 1].from = methodModel.fixedPricePolicy[index].from;
            }

            methodModel.fixedPricePolicy.splice(index, 1);
            displayFixedPricesSubform(false, false);
        }

        /**
         * Displays packlink percent subform.
         */
        function displayPercentForm() {
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

            methodModel.pricePolicy = PRICING_POLICY_PERCENT;

            if (!methodModel.percentPricePolicy) {
                methodModel.percentPricePolicy = {
                    increase: true,
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
            displayPercentForm();
        }

        /**
         * Fills tax selector.
         *
         * @param response
         */
        function getTaxClassesSuccessHandler(response) {
            taxSelector = templateService.getComponent('pl-tax-selector', document);

            for (let taxClass of response) {
                let option = document.createElement('option');
                option.value = taxClass['value'];
                option.innerHTML = taxClass['label'];
                taxSelector.appendChild(option);
                taxClasses.push(option['value']);
            }

            taxSelector.value = response[0]['value'];

            if (spinnerBarrier === spinnerBarrierCount) {
                utilityService.hideSpinner();
            } else {
                spinnerBarrierCount++;
            }
        }

        /**
         * Checks whether tax class exists in system.
         *
         * @param taxClass
         *
         * @return {boolean}
         */
        function classExists(taxClass) {
            for (taxClassValue of taxClasses) {
                if (taxClassValue == taxClass) {
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
            hideNoShippingMethodsMessage();
            isDashboardShowed = true;
        }

        /**
         * Hides dashboard modal.
         */
        function hideDashboardModal() {
            if (!dashboardData.parcelSet || !dashboardData.warehouseSet) {
                return;
            }

            templateService.getComponent('pl-dashboard-modal-wrapper', extensionPoint).classList.add('hidden');
            if (Object.keys(shippingMethods).length === 0) {
                showNoShippingMethodsMessage();
            }
            isDashboardShowed = false;
        }
    }

    Packlink.ShippingMethodsController = ShippingMethodsController;
})();
