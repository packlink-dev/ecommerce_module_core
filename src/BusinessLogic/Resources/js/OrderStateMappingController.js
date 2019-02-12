var Packlink = window.Packlink || {};

(function () {
    function OrderStateMappingController(configuration) {
        let templateService = Packlink.templateService;
        let utilityService = Packlink.utilityService;
        let ajaxService = Packlink.ajaxService;
        let state = Packlink.state;

        const statuses = [
            'pending',
            'processing',
            'readyForShipping',
            'inTransit',
            'delivered',
        ];

        let page;
        let mappings = {};

        let binaryGate = false;

        // Register public methods and variables.
        this.display = display;

        function display() {
            utilityService.showSpinner();

            page = templateService.setTemplate('pl-order-state-mapping-template');

            attachEventHandlers();

            ajaxService.get(configuration.getSystemOrderStatusesUrl, getSystemOrderStatusesSuccessHandler);
            ajaxService.get(configuration.getUrl, getMappingsSuccessfulHandler);
        }

        /**
         * Attaches event handlers to form components.
         */
        function attachEventHandlers() {
            for (let status of statuses) {
                let select = templateService.getComponent('data-pl-status', page, status);
                select.addEventListener('change', mappingChangedHandler, true);
            }

            let btn = templateService.getComponent('pl-save-mappings-btn', page);
            btn.addEventListener('click', saveOrderStatusMappings, true);
        }

        /**
         * Handles mapping changed event.
         *
         * @param event
         */
        function mappingChangedHandler(event) {
            let value = event.target.value;
            let status = event.target.getAttribute('data-pl-status');

            if (value === '') {
                delete mappings[status];
            } else {
                mappings[status] = value;
            }
        }

        /**
         * Saves order status mappings.
         */
        function saveOrderStatusMappings() {
            utilityService.showSpinner();
            ajaxService.post(
                configuration.saveUrl,
                mappings,
                function (response) {
                    utilityService.hideSpinner();

                    if (configuration.fromStep) {
                        state.stepFinished();
                    }
                }
            )
        }

        /**
         * Handles successful retrieval of order statuses.
         *
         * @param {object[]} response
         */
        function getSystemOrderStatusesSuccessHandler(response) {
            for (let status of statuses) {
                let select = templateService.getComponent('data-pl-status', page, status);
                addSelectOptions(select, response);
            }

            if (binaryGate) {
                completePageLoad();
            }

            binaryGate = true;
        }

        /**
         * Adds options to select html element.
         *
         * @param {Element} select
         * @param {object[]} response
         */
        function addSelectOptions(select, response) {
            for (let option of response) {
                let optionField = document.createElement('option');
                optionField.value = option.code;
                optionField.innerHTML = option.label;

                select.appendChild(optionField);
            }
        }

        /**
         * Handles successful retrieval of mapped order statuses.
         *
         * @param response
         */
        function getMappingsSuccessfulHandler(response) {
            if (response.length !== 0) {
                mappings = response;
            }

            if (binaryGate) {
                completePageLoad();
            }

            binaryGate = true;
        }

        /**
         * Finalizes page load by applying selected mappings to selection form.
         */
        function completePageLoad() {
            for (let status in mappings) {
                if (mappings.hasOwnProperty(status)) {
                    let select = templateService.getComponent('data-pl-status', page, status);
                    select.value = mappings[status];
                }
            }

            utilityService.hideSpinner();
        }
    }

    Packlink.OrderStateMappingController = OrderStateMappingController;
})();