if (!window.Packlink) {
    window.Packlink = {};
}

(function () {
    /**
     * @typedef ShippingService
     * @property {string} id
     * @property {boolean} activated
     * @property {string} name
     * @property {string} logoUrl
     * @property {string} type
     * @property {string} deliveryType
     * @property {string} carrierName
     * @property {string} deliveryDescription
     * @property {'dropoff'|'collection'} parcelOrigin
     * @property {'pickup'|'delivery'} parcelDestination
     * @property {boolean} showLogo
     * @property {string} taxClass
     * @property {[]} pricingPolicies
     * @property {string} currency
     * @property {[]} shippingCountries
     * @property {boolean} isShipToAllCountries
     * @property {boolean} usePacklinkPriceIfNotInRange
     */

    /**
     * Renders shipping services table.
     *
     * @constructor
     */
    function ShippingServicesRenderer() {
        const templateService = Packlink.templateService,
            translator = Packlink.translationService;

        /**
         * Renders the services table.
         *
         * @param {HTMLElement} parent
         * @param {string} templateId
         * @param {string} elementType
         * @param {ShippingService[]} services
         * @param {boolean} list
         * @param {function(id: string, action: 'add'|'edit'|'delete')} buttonAction
         */
        this.render = (parent, templateId, elementType, services, list, buttonAction) => {
            parent.innerHTML = '';
            services.forEach((service) => {
                const template = templateService.getComponent(templateId),
                    itemEl = document.createElement(elementType);

                itemEl.innerHTML = template.innerHTML;
                constructItem(itemEl, service, list, buttonAction);

                parent.appendChild(itemEl);
            });
        };

        /**
         * Fills row template with concrete information such as title etc.
         * Also, attaches proper event handlers to actionable elements of row template.
         *
         * @param {Element} itemEl
         * @param {ShippingService} service
         * @param {boolean} list
         * @param {function(id: string, action: 'add'|'edit'|'delete')} buttonAction
         */
        function constructItem(itemEl, service, list, buttonAction) {
            const carrierLogo = itemEl.querySelector('#pl-carrier-logo');
            carrierLogo.setAttribute('src', service.logoUrl);
            carrierLogo.setAttribute('alt', service.carrierName);
            carrierLogo.setAttribute('title', service.carrierName);

            itemEl.querySelector('#pl-service-name').innerHTML = service.name;
            itemEl.querySelector('#pl-service-policy').innerHTML = translator.translate('shippingServices.' + (service.pricingPolicies.length ? 'myPrices' : 'packlinkPrices')) + ' (' + service.currency + ')';

            itemEl.querySelector('#pl-service-delivery-description').innerHTML = service.deliveryDescription;
            itemEl.querySelector('#pl-service-type').innerHTML = translator.translate('shippingServices.' + service.type);

            const parcelOrigin = itemEl.querySelector('#pl-service-origin');
            if (service.parcelOrigin === 'collection') {
                parcelOrigin.querySelector('#pl-origin-dropoff').classList.add('pl-hidden');
            } else {
                parcelOrigin.querySelector('#pl-origin-collection').classList.add('pl-hidden');
            }

            const parcelDestination = itemEl.querySelector('#pl-service-destination');
            if (service.parcelDestination === 'delivery') {
                parcelDestination.querySelector('#pl-destination-pickup').classList.add('pl-hidden');
            } else {
                parcelDestination.querySelector('#pl-destination-delivery').classList.add('pl-hidden');
            }

            const addButton = itemEl.querySelector('#pl-service-actions #pl-add-service'),
                editButton = itemEl.querySelector('#pl-service-actions #pl-edit-service'),
                deleteButton = itemEl.querySelector('#pl-service-actions #pl-delete-service');

            if (list) {
                addButton.classList.add('pl-hidden');
            } else {
                editButton.classList.add('pl-hidden');
                deleteButton.classList.add('pl-hidden');
            }

            addButton.addEventListener('click', () => {
                buttonAction(service.id, 'add');
            });
            editButton.addEventListener('click', () => {
                buttonAction(service.id, 'edit');
            });
            deleteButton.addEventListener('click', () => {
                buttonAction(service.id, 'delete');
            });
        }
    }

    Packlink.ShippingServicesRenderer = new ShippingServicesRenderer();
})();
