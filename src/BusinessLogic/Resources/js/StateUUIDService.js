var CleverReach = window.CleverReach || {};

(()=>{
    function StateUUIDService() {
        let currentState = '';

        this.setStateUUID = (state) => {
            currentState = state;
        }

        this.getStateUUID = () => {
            return currentState;
        }
    }

    CleverReach.StateUUIDService = new StateUUIDService();
})();