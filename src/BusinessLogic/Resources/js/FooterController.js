var Packlink = window.Packlink || {};

(function () {
    function FooterControllerConstructor(config) {
        let footer;
        let templateService = Packlink.templateService;
        let isSystemInfoOpen = false;
        let systemInfoPanel;
        let debugModeCheckbox;

        let ajaxService = Packlink.ajaxService;

        let debugStatus = false;

        // Register public functions and methods.
        this.display = display;

        function display() {
            footer = templateService.getComponent('pl-footer-extension-point');
            let templateComponents = templateService.getTemplate('pl-footer-template');
            for (let component of templateComponents) {
                footer.appendChild(component);
            }

            systemInfoPanel = templateService.getComponent('pl-system-info-panel', footer);
            templateService.getComponent('pl-system-info-open-btn', footer).addEventListener(
                'click',
                openSystemInfo
            );
            templateService.getComponent('pl-system-info-close-btn', footer).addEventListener(
                'click',
                closeSystemInfo
            );

            debugModeCheckbox = templateService.getComponent('pl-debug-mode-checkbox', footer);
            debugModeCheckbox.addEventListener('click', debugModeCheckboxClickedHandler);

            ajaxService.get(config.getStatusUrl, getDebugStatusHandler);
        }

        /**
         * Handles retrieving debug status.
         */
        function getDebugStatusHandler(response) {
            debugStatus = response.status;
            debugModeCheckbox.checked = debugStatus;
            systemInfoPanel.classList.remove('loading');
        }

        /**
         * Handles click event on debug mode checkbox.
         */
        function debugModeCheckboxClickedHandler() {
            systemInfoPanel.classList.add('loading');
            debugStatus = !debugStatus;

            ajaxService.post(config.setStatusUrl, {status: debugStatus}, function (response) {
                debugStatus = response.status;
                debugModeCheckbox.checked = debugStatus;
                systemInfoPanel.classList.remove('loading');
            });
        }

        /**
         * Opens system info panel.
         */
        function closeSystemInfo() {
            if (isSystemInfoOpen) {
                systemInfoPanel.classList.add('hidden');
                isSystemInfoOpen = false;
            }
        }

        /**
         * Closes system info panel.
         */
        function openSystemInfo() {
            if (!isSystemInfoOpen) {
                systemInfoPanel.classList.remove('hidden');
                isSystemInfoOpen = true;
            }
        }
    }

    Packlink.FooterController = FooterControllerConstructor;
})();