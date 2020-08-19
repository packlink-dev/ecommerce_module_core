if (!window.Packlink) {
    window.Packlink = {};
}

(() => {
    function StateUUIDService() {
        let currentState = '';

        this.setStateUUID = (state) => {
            currentState = state;
        };

        this.getStateUUID = () => {
            return currentState;
        };
    }

    Packlink.StateUUIDService = new StateUUIDService();
})();