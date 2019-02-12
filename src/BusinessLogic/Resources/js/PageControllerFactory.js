var Packlink = window.Packlink || {};

(function () {
    function PageControllerFactory() {

        //Register public methods
        this.getInstance = getInstance;

        /**
         * Instantiates page controller;
         *
         * @param {string} controller
         * @param {object} configuration
         */
        function getInstance(controller, configuration) {
            let parts = controller.split('-');
            let name = '';
            for (let part of parts) {
                part = part.charAt(0).toUpperCase() + part.slice(1);
                name += part;
            }

            name += 'Controller';

            return new Packlink[name](configuration);
        }
    }

    Packlink.pageControllerFactory = new PageControllerFactory();
})();