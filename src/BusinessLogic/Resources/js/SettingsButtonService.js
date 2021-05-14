if (!window.Packlink) {
    window.Packlink = {};
}

(function () {
    /**
     * @constructor
     */
    function SettingsButtonService() {
        /**
         * Displays page content.
         */
        this.displaySettings = function (settingsMenu, state) {
            settingsMenu.addEventListener('click', () => {
                state.goToState('configuration');
            });
        };
    }

    Packlink.settingsButtonService = new SettingsButtonService();
})();
