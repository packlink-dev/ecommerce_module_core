var Packlink = window.Packlink || {};

(function () {
    function SidebarController(sidebarNavigationCallback, sidebarButtons, submenuItems) {
        let templateService = Packlink.templateService;
        let navigationCallback = sidebarNavigationCallback;
        let currentState = 'shipping-methods';
        let basicSettingsSubmenuDisplayed = false;

        /**
         * Sets currently selected button on dashboard.
         *
         * @param {string} state
         */
        this.setState = function (state) {
            if (state === 'basic-settings') {
                handleBasicSettingsMenuAction();
            } else {
                removeSelectedClass(currentState);
                addSelectedClass(state);

                if (basicSettingsSubmenuDisplayed && (submenuItems.indexOf(state) === -1)) {
                    hideBasicSettingsSubmenu();
                }

                currentState = state;
            }
        };

        function handleBasicSettingsMenuAction() {
            if (!basicSettingsSubmenuDisplayed) {
                displayBasicSettingsSubmenu();
            } else {
                if (submenuItems.indexOf(currentState) === -1) {
                    hideBasicSettingsSubmenu();
                }
            }
        }

        /**
         * Removes "selected" class from target button.
         *
         * @param {string} targetButton
         */
        function removeSelectedClass(targetButton) {
            let currentButton = templateService.getComponent(getButtonId(targetButton));
            currentButton.classList.remove('selected');
        }

        /**
         * Adds "selected" class from target button.
         *
         * @param {string} targetButton
         */
        function addSelectedClass(targetButton) {
            let currentButton = templateService.getComponent(getButtonId(targetButton));
            currentButton.classList.add('selected');
        }

        /**
         * Adds click event handlers to initial sidebar buttons.
         * Doesn't add event handlers to submenu buttons as they are not
         * initially displayed.
         *
         * @param {function} sidebarNavigationCallback
         */
        function addEventHandlersToSidebarButtons(sidebarNavigationCallback) {
            for (let buttonId of sidebarButtons) {
                let button = templateService.getComponent(getButtonId(buttonId));
                button.addEventListener('click', sidebarNavigationCallback, true);
                if (buttonId === 'shipping-methods') {
                    button.classList.add('selected');
                }
            }
        }

        /**
         * Displays basic settings submenu.
         */
        function displayBasicSettingsSubmenu() {
            let sidebarButtons = templateService.getTemplate('pl-sidebar-subitem-template');
            let submenuExtensionPoint = templateService.getComponent('pl-sidebar-extension-point');
            while (sidebarButtons.length) {
                let button = sidebarButtons[0];
                button.addEventListener('click', navigationCallback, true);
                button.setAttribute('id', getButtonId(button.getAttribute('data-pl-sidebar-btn')));
                submenuExtensionPoint.appendChild(button);
            }

            addSelectedClass('basic-settings');
            basicSettingsSubmenuDisplayed = true;
        }

        /**
         * Hides basic settings submenu.
         */
        function hideBasicSettingsSubmenu() {
            let submenuExtensionPoint = templateService.getComponent('pl-sidebar-extension-point');
            templateService.clearComponent(submenuExtensionPoint);

            removeSelectedClass('basic-settings');
            basicSettingsSubmenuDisplayed = false;
        }

        /**
         * Formats button id.
         *
         * @param {string} name
         * @return {string}
         */
        function getButtonId(name) {
            return 'pl-sidebar-' + name + '-btn';
        }

        addEventHandlersToSidebarButtons(navigationCallback);
    }

    Packlink.SidebarController = SidebarController;
})();