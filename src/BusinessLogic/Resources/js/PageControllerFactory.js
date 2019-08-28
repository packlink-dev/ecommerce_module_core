var Packlink = window.Packlink || {};

(function () {
    function PageControllerFactory() {
        /**
         * Instantiates page controller;
         *
         * @param {string} controller
         * @param {object} configuration
         */
        this.getInstance = function (controller, configuration) {
            let parts = controller.split('-');
            let name = '';
            for (let part of parts) {
                part = part.charAt(0).toUpperCase() + part.slice(1);
                name += part;
            }

            name += 'Controller';

            return new Packlink[name](configuration);
        };
    }

    Packlink.pageControllerFactory = new PageControllerFactory();
})();