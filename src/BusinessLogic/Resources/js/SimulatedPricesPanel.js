if (!window.Packlink) {
    window.Packlink = {};
}

(function () {
    const PAGE_SIZE = 4;
    const MIN_SEARCH_LENGTH = 3;
    const ERROR_MESSAGES = {
        market_unavailable: 'simulatedPrices.marketUnavailable',
        validation: 'simulatedPrices.validation',
        too_many_requests: 'simulatedPrices.tooManyRequests'
    };

    /**
     * "Discover our shipping rates" panel (CR-SET-72-02). Quotes a fixed sample parcel on every Packlink PRO
     * plan when the panel opens, when a country chip is picked, and when a destination is picked. Every quote
     * is one Packlink call; nothing is cached.
     *
     * @param {{dataUrl: string, searchUrl: string, locationSearchUrl: string}} configuration
     * @constructor
     */
    function SimulatedPricesPanel(configuration) {
        const ajaxService = Packlink.ajaxService,
            translator = Packlink.translationService,
            utilityService = Packlink.utilityService;

        let data = null,
            market = null,
            destinationCountry = null,
            result = null,
            expanded = false,
            latestRequest = 0,
            elements = {};

        /**
         * Loads the panel data and renders the panel into the container. The container stays hidden when the
         * merchant may not see simulated prices (PREMIUM plan) or the data cannot be loaded.
         *
         * @param {HTMLElement} container
         * @param {{onClose?: function, onReady?: function}} [options]
         */
        this.render = (container, options) => {
            options = options || {};
            utilityService.hideElement(container);
            ajaxService.get(configuration.dataUrl, (response) => {
                if (!response || !response.enabled || !response.markets || response.markets.length === 0) {
                    return;
                }

                data = response;
                // A connected merchant is quoted on its own account's market (core fixes it): start there.
                const defaultMarket = (data.connected && findMarket(data.accountMarket))
                    || findMarket(data.defaultMarket) || data.markets[0];
                market = defaultMarket.code;
                destinationCountry = defaultMarket.code;
                build(container, options.onClose);
                utilityService.showElement(container);
                setDestinationText(defaultMarket);
                quote(defaultMarket.postalCode);

                if (options.onReady) {
                    options.onReady();
                }
            }, () => null);
        };

        /**
         * @param {HTMLElement} container
         * @param {function} [onClose]
         */
        const build = (container, onClose) => {
            container.innerHTML = '';
            container.classList.add('pl-simulated-prices-panel');

            const header = el('div', 'pl-simulated-prices-header');
            header.appendChild(el('h2', 'pl-simulated-prices-title', translator.translate('simulatedPrices.title')));
            if (onClose) {
                const close = el('button', 'pl-simulated-prices-close');
                close.type = 'button';
                close.setAttribute('aria-label', translator.translate('simulatedPrices.close'));
                close.appendChild(el('i', 'material-icons', 'close'));
                close.addEventListener('click', onClose);
                header.appendChild(close);
            }

            container.appendChild(header);
            container.appendChild(el('p', 'pl-simulated-prices-description', translator.translate('simulatedPrices.description')));

            const controls = el('div', 'pl-simulated-prices-controls');
            const chips = el('div', 'pl-simulated-prices-chips');
            chips.setAttribute('role', 'group');
            data.markets.forEach((item) => {
                const chip = el('button', 'pl-simulated-prices-chip');
                chip.type = 'button';
                chip.setAttribute('data-market', item.code);
                chip.appendChild(flag(item.code));
                chip.appendChild(document.createTextNode(marketName(item.code)));
                chip.addEventListener('click', () => onMarketPick(item.code));
                chips.appendChild(chip);
            });
            // A connected merchant has no market to choose: no chips.
            if (!data.connected) {
                controls.appendChild(chips);
            }

            const destination = el('div', 'pl-simulated-prices-destination');
            destination.appendChild(el('span', 'pl-simulated-prices-destination-label', translator.translate('simulatedPrices.destination')));
            // A native <select> cannot show images in its list, so the country list is a listbox of flags.
            const picker = el('div', 'pl-simulated-prices-country');
            const button = el('button', 'pl-simulated-prices-country-button');
            button.type = 'button';
            button.setAttribute('aria-haspopup', 'listbox');
            button.setAttribute('aria-expanded', 'false');
            elements.destinationFlag = flag(destinationCountry);
            button.appendChild(elements.destinationFlag);
            button.appendChild(el('span', 'pl-simulated-prices-country-arrow'));
            const list = el('ul', 'pl-simulated-prices-country-list pl-hidden');
            list.setAttribute('role', 'listbox');
            list.setAttribute('aria-label', translator.translate('simulatedPrices.destination'));
            data.markets.forEach((item) => {
                const option = el('li', 'pl-simulated-prices-country-option');
                option.setAttribute('role', 'option');
                option.setAttribute('data-country', item.code);
                option.appendChild(flag(item.code));
                option.appendChild(document.createTextNode(marketName(item.code)));
                option.addEventListener('click', () => {
                    toggleCountryList(false);
                    if (item.code !== destinationCountry) {
                        onDestinationCountry(item.code);
                    }
                });
                list.appendChild(option);
            });
            button.addEventListener('click', () => toggleCountryList(list.classList.contains('pl-hidden')));
            document.addEventListener('click', (event) => {
                if (!picker.contains(event.target)) {
                    toggleCountryList(false);
                }
            });
            picker.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    toggleCountryList(false);
                    button.focus();
                }
            });
            picker.appendChild(button);
            picker.appendChild(list);
            elements.countryButton = button;
            elements.countryList = list;

            const searchWrapper = el('div', 'pl-simulated-prices-search');
            const input = el('input', 'pl-simulated-prices-search-input');
            input.type = 'text';
            input.setAttribute('aria-label', translator.translate('simulatedPrices.destinationPlaceholder'));
            input.placeholder = translator.translate('simulatedPrices.destinationPlaceholder');
            input.addEventListener('input', utilityService.debounce(250, () => onDestinationInput(input.value)));
            elements.input = input;
            elements.options = el('div', 'pl-simulated-prices-options pl-hidden');
            searchWrapper.appendChild(input);
            searchWrapper.appendChild(elements.options);

            destination.appendChild(picker);
            destination.appendChild(searchWrapper);
            controls.appendChild(destination);
            container.appendChild(controls);

            elements.chips = chips;
            elements.results = el('div', 'pl-simulated-prices-results');
            container.appendChild(elements.results);
            container.appendChild(el('p', 'pl-simulated-prices-disclaimer', translator.translate('simulatedPrices.disclaimer')));

            updateChips();
        };

        const onMarketPick = (code) => {
            market = code;
            updateChips();
            quote(applyCapital(code));
        };

        const onDestinationCountry = (code) => {
            quote(applyCapital(code));
        };

        const onDestinationInput = (value) => {
            if (value.length < MIN_SEARCH_LENGTH) {
                renderOptions([]);

                return;
            }

            ajaxService.post(
                configuration.locationSearchUrl,
                {country: destinationCountry, query: value},
                (response) => renderOptions(Array.isArray(response) ? response : []),
                () => renderOptions([])
            );
        };

        /**
         * @param {{id: string, text: string, zipcode: string}[]} locations
         */
        const renderOptions = (locations) => {
            elements.options.innerHTML = '';
            locations.forEach((location) => {
                const option = el('div', 'pl-simulated-prices-option', location.text);
                option.addEventListener('click', () => {
                    elements.input.value = location.text;
                    renderOptions([]);
                    quote(location.zipcode);
                });
                elements.options.appendChild(option);
            });

            if (locations.length > 0) {
                utilityService.showElement(elements.options);
            } else {
                utilityService.hideElement(elements.options);
            }
        };

        /**
         * Shows the destination country on the flag button and marks it in the list.
         */
        const showDestinationCountry = () => {
            elements.destinationFlag.src = flagUrl(destinationCountry);
            elements.countryButton.setAttribute('aria-label', translator.translate('simulatedPrices.destination') + ': ' + marketName(destinationCountry));
            Array.prototype.forEach.call(elements.countryList.children, (option) => {
                option.setAttribute('aria-selected', option.getAttribute('data-country') === destinationCountry ? 'true' : 'false');
            });
        };

        /**
         * Opens or closes the destination country list.
         *
         * @param {boolean} open
         */
        const toggleCountryList = (open) => {
            elements.countryList.classList.toggle('pl-hidden', !open);
            elements.countryButton.setAttribute('aria-expanded', open ? 'true' : 'false');
        };

        /**
         * Sets the destination to the capital city of a chip country and returns its postal code.
         *
         * @param {string} code
         * @return {string}
         */
        const applyCapital = (code) => {
            const capital = findMarket(code);
            destinationCountry = code;
            showDestinationCountry();
            setDestinationText(capital);
            renderOptions([]);

            return capital ? capital.postalCode : '';
        };

        const setDestinationText = (capital) => {
            showDestinationCountry();
            elements.input.value = capital ? capital.postalCode + ' - ' + capital.city : '';
        };

        /**
         * @param {string} postalCode
         */
        const quote = (postalCode) => {
            const requestId = ++latestRequest;
            renderLoading();
            const payload = {market: market, toCountry: destinationCountry, toPostalCode: postalCode};
            const done = (response) => {
                // A slower earlier answer must not overwrite the answer to the latest choice.
                if (requestId !== latestRequest) {
                    return;
                }

                result = response && Array.isArray(response.services)
                    ? response
                    : {services: [], tiers: [], error: (response && response.error) || 'packlink_error'};
                expanded = false;
                renderResult();
            };

            ajaxService.post(configuration.searchUrl, payload, done, done);
        };

        const renderLoading = () => {
            elements.results.innerHTML = '';
            const loading = el('div', 'pl-simulated-prices-message');
            loading.appendChild(el('div', 'pl-simulated-prices-spinner'));
            elements.results.appendChild(loading);
        };

        const renderResult = () => {
            const box = elements.results;
            box.innerHTML = '';

            if (result.error || result.services.length === 0) {
                const message = el('div', 'pl-simulated-prices-message');
                message.appendChild(el('i', 'material-icons', 'error_outline'));
                if (!result.error || result.error === 'no_services') {
                    message.appendChild(el('strong', '', translator.translate('simulatedPrices.noServicesTitle')));
                    message.appendChild(el('span', '', translator.translate('simulatedPrices.noServicesHint')));
                } else {
                    message.appendChild(el('span', '', translator.translate(
                        ERROR_MESSAGES[result.error] || 'simulatedPrices.packlinkError'
                    )));
                }

                box.appendChild(message);

                return;
            }

            const card = el('div', 'pl-simulated-prices-card');
            if (result.fromPrice) {
                const note = el('div', 'pl-simulated-prices-note');
                note.appendChild(el('i', 'material-icons', 'inventory_2'));
                const text = el('div');
                const price = formatPrice(result.fromPrice.totalPrice, result.fromPrice.currency);
                text.appendChild(el('strong', '', result.connected
                    ? translator.translate('simulatedPrices.startShippingFromWithPlan', [price, planName(result.fromPrice.tier)])
                    : translator.translate('simulatedPrices.startShippingFrom', [price])));
                text.appendChild(el('span', '', translator.translate('simulatedPrices.parcelNote')));
                note.appendChild(text);
                card.appendChild(note);
            }

            const table = el('table', 'pl-simulated-prices-table');
            // Column widths come from the colgroup, not from the visible rows, so the plan columns stay in
            // place when "Show more" adds rows with longer carrier names.
            const columns = el('colgroup');
            columns.appendChild(el('col'));
            result.tiers.forEach(() => columns.appendChild(el('col', 'pl-simulated-prices-plan-col')));
            table.appendChild(columns);
            const head = el('tr');
            head.appendChild(el('th', '', translator.translate('simulatedPrices.carrier')));
            result.tiers.forEach((tier) => {
                const th = el('th', tier === 'PREMIUM' ? 'pl-simulated-prices-highlight' : '');
                const plan = el('div', 'pl-simulated-prices-plan');
                plan.appendChild(el('span', '', planName(tier)));
                if (result.connected && tier === result.currentTier) {
                    plan.appendChild(el('span', 'pl-simulated-prices-tag pl-simulated-prices-current', translator.translate('simulatedPrices.currentPlan')));
                } else if (result.connected && data.upgradeUrl) {
                    const upgrade = el('a', 'pl-simulated-prices-tag pl-simulated-prices-upgrade', translator.translate('simulatedPrices.upgrade'));
                    upgrade.href = data.upgradeUrl;
                    upgrade.target = '_blank';
                    upgrade.rel = 'noopener noreferrer';
                    plan.appendChild(upgrade);
                }

                th.appendChild(plan);
                head.appendChild(th);
            });

            const thead = el('thead');
            thead.appendChild(head);
            table.appendChild(thead);

            const body = el('tbody');
            const visible = expanded ? result.services : result.services.slice(0, PAGE_SIZE);
            visible.forEach((service) => {
                const row = el('tr');
                // The flex box sits inside the cell: a flex <th> stops behaving as a table cell.
                const cell = el('th');
                cell.setAttribute('scope', 'row');
                const carrier = el('div', 'pl-simulated-prices-carrier');
                if (service.logoUrl) {
                    const logo = el('img');
                    logo.src = service.logoUrl;
                    logo.alt = '';
                    carrier.appendChild(logo);
                }

                const details = [service.serviceName, transit(service)].filter((part) => !!part).join(' · ');
                const names = el('div', 'pl-simulated-prices-names');
                const name = el('span', '', service.carrierName);
                name.title = service.carrierName;
                const detail = el('span', 'pl-simulated-prices-service', details);
                detail.title = details;
                names.appendChild(name);
                names.appendChild(detail);
                carrier.appendChild(names);
                cell.appendChild(carrier);
                row.appendChild(cell);

                result.tiers.forEach((tier) => {
                    const price = service.prices.filter((item) => item.tier === tier)[0];
                    row.appendChild(el(
                        'td',
                        tier === 'PREMIUM' ? 'pl-simulated-prices-highlight' : '',
                        price ? formatPrice(price.totalPrice, price.currency) : '–'
                    ));
                });

                body.appendChild(row);
            });

            table.appendChild(body);
            const scroll = el('div', expanded ? 'pl-simulated-prices-scroll pl-simulated-prices-expanded' : 'pl-simulated-prices-scroll');
            scroll.appendChild(table);
            card.appendChild(scroll);

            const rest = result.services.length - PAGE_SIZE;
            if (rest > 0) {
                const toggle = el('button', 'pl-simulated-prices-toggle', expanded
                    ? translator.translate('simulatedPrices.showLess')
                    : translator.translate('simulatedPrices.showMore', [rest]));
                toggle.type = 'button';
                toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                toggle.addEventListener('click', () => {
                    expanded = !expanded;
                    renderResult();
                });
                card.appendChild(toggle);
            }

            box.appendChild(card);
        };

        const updateChips = () => {
            elements.chips.querySelectorAll('.pl-simulated-prices-chip').forEach((chip) => {
                chip.setAttribute('aria-pressed', chip.getAttribute('data-market') === market ? 'true' : 'false');
            });
        };

        /**
         * @param {{transitTime: string|null}} service
         * @return {string}
         */
        const transit = (service) => {
            const days = parseInt(service.transitTime || '', 10);
            if (isNaN(days)) {
                return '';
            }

            return days === 1
                ? translator.translate('simulatedPrices.businessDay')
                : translator.translate('simulatedPrices.businessDays', [days]);
        };

        const formatPrice = (amount, currency) => {
            try {
                return new Intl.NumberFormat(document.documentElement.lang || undefined, {
                    style: 'currency',
                    currency: currency
                }).format(amount);
            } catch (e) {
                return amount + ' ' + currency;
            }
        };

        const planName = (tier) => translator.translate('simulatedPrices.plan' + tier.charAt(0) + tier.slice(1).toLowerCase());

        const marketName = (code) => translator.translate('simulatedPrices.market' + code);

        const findMarket = (code) => data.markets.filter((item) => item.code === code)[0] || null;

        const flagUrl = (code) => 'https://flagcdn.com/w40/' + code.toLowerCase() + '.png';

        /**
         * @param {string} code
         * @return {HTMLImageElement}
         */
        const flag = (code) => {
            const image = el('img', 'pl-simulated-prices-flag');
            image.src = flagUrl(code);
            image.alt = '';

            return image;
        };

        /**
         * Creates an element; text is set as text content, never as HTML.
         *
         * @param {string} tag
         * @param {string} [className]
         * @param {string} [text]
         * @return {HTMLElement}
         */
        const el = (tag, className, text) => {
            const element = document.createElement(tag);
            if (className) {
                element.className = className;
            }

            if (text !== undefined && text !== null) {
                element.textContent = text;
            }

            return element;
        };
    }

    /**
     * Creates the panel from a page configuration, or returns null when the module does not provide the panel
     * URLs. Modules opt in by adding simulatedPricesDataUrl, simulatedPricesSearchUrl and
     * simulatedPricesLocationSearchUrl to the page configuration.
     *
     * @param {{simulatedPricesDataUrl?: string, simulatedPricesSearchUrl?: string,
     *     simulatedPricesLocationSearchUrl?: string}} configuration
     * @return {SimulatedPricesPanel|null}
     */
    SimulatedPricesPanel.fromPageConfiguration = (configuration) => {
        if (!configuration || !configuration.simulatedPricesDataUrl || !configuration.simulatedPricesSearchUrl
            || !configuration.simulatedPricesLocationSearchUrl) {
            return null;
        }

        return new SimulatedPricesPanel({
            dataUrl: configuration.simulatedPricesDataUrl,
            searchUrl: configuration.simulatedPricesSearchUrl,
            locationSearchUrl: configuration.simulatedPricesLocationSearchUrl
        });
    };

    /**
     * Shows the panel next to the content of a login or registration page when the module provides the panel
     * URLs. The page layout is unchanged until the panel has data to show.
     *
     * @param {HTMLElement|null} page The page root element.
     * @param {object} configuration Page configuration.
     */
    SimulatedPricesPanel.attachBesidePage = (page, configuration) => {
        if (!page) {
            return;
        }

        const panel = SimulatedPricesPanel.fromPageConfiguration(configuration);
        if (!panel) {
            return;
        }

        const container = document.createElement('section');
        container.className = 'pl-simulated-prices-panel pl-hidden';
        page.appendChild(container);
        panel.render(container, {
            onReady: () => page.classList.add('pl-with-simulated-prices')
        });
    };

    Packlink.SimulatedPricesPanel = SimulatedPricesPanel;
})();
