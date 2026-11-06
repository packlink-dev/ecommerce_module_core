if (!window.Packlink) {
    window.Packlink = {};
}

(function () {
    const PAGE_SIZE = 4;
    // Icons of the design (stroke SVGs); static markup only.
    const SVG = (size, body, width) => '<svg width="' + size + '" height="' + size + '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="' + (width || 2) + '" stroke-linecap="round" stroke-linejoin="round">' + body + '</svg>';
    const ICONS = {
        close: SVG(20, '<path d="M6 6l12 12M18 6 6 18"></path>', 2.4),
        upDown: SVG(12, '<path d="M7 9l5-5 5 5M7 15l5 5 5-5"></path>', 2.4),
        search: SVG(14, '<circle cx="11" cy="11" r="7"></circle><path d="M20 20l-4-4"></path>', 2.2),
        box: SVG(26, '<path d="M3 7.5 12 3l9 4.5v9L12 21l-9-4.5z"></path><path d="M3 7.5 12 12l9-4.5M12 12v9"></path>', 1.6),
        plus: SVG(10, '<path d="M12 5v14M5 12h14"></path>', 3),
        chevronDown: SVG(16, '<path d="M6 9l6 6 6-6"></path>', 2.4),
        chevronUp: SVG(16, '<path d="M6 15l6-6 6 6"></path>', 2.4),
        truck: SVG(20, '<path d="M3 7h11v9H3z"></path><path d="M14 10h4l3 3v3h-7"></path><circle cx="7" cy="17.5" r="1.8"></circle><circle cx="17" cy="17.5" r="1.8"></circle>')
    };
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
     * @param {{dataUrl: string, searchUrl: string, locationSearchUrl: string, initialData: ?Object}} configuration
     *   initialData: the panel data rendered into the page (core SimulatedPricesController::getConfiguration()),
     *   used on pages of a merchant without an account so the panel is drawn without waiting for a request.
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
            outsideClickHandler = null,
            latestRequest = 0,
            elements = {};

        /**
         * Loads the panel data and renders the panel into the container. The container stays hidden when the
         * merchant may not see simulated prices (PREMIUM plan) or the data cannot be loaded.
         *
         * The panel is drawn at once as a placeholder of its final size (title, introduction, a loading card),
         * so the page does not shift when the data and the prices arrive.
         *
         * @param {HTMLElement} container
         * @param {{onClose?: function, onReady?: function, onUnavailable?: function, introduction?: boolean}} [options]
         */
        this.render = (container, options) => {
            options = options || {};
            buildPlaceholder(container, options);
            utilityService.showElement(container);
            const unavailable = () => {
                utilityService.hideElement(container);
                if (options.onUnavailable) {
                    options.onUnavailable();
                }
            };

            const start = (response) => {
                if (!response || !response.enabled || !response.markets || response.markets.length === 0) {
                    unavailable();

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
            };

            if (configuration.initialData && options.introduction) {
                // Login and registration: no account yet, so the rendered configuration is the whole data.
                start(Object.assign({connected: false, currentTier: null, upgradeUrl: null}, configuration.initialData));

                return;
            }

            ajaxService.get(configuration.dataUrl, start, unavailable);
        };

        /**
         * Title, introduction (when asked for) and a loading card, before the data arrives.
         *
         * @param {HTMLElement} container
         * @param {{onClose?: function, introduction?: boolean}} options
         */
        const buildPlaceholder = (container, options) => {
            container.innerHTML = '';
            container.classList.add('pl-simulated-prices-panel');
            container.appendChild(buildHeader(options.onClose));
            if (options.introduction) {
                container.appendChild(el('p', 'pl-simulated-prices-description', translator.translate('simulatedPrices.description')));
            }

            container.appendChild(el('div', 'pl-simulated-prices-controls'));
            elements.results = el('div', 'pl-simulated-prices-results');
            container.appendChild(elements.results);
            renderLoading();
        };

        /**
         * @param {function} [onClose]
         * @return {HTMLElement}
         */
        const buildHeader = (onClose) => {
            const header = el('div', 'pl-simulated-prices-header');
            header.appendChild(el('h2', 'pl-simulated-prices-title', translator.translate('simulatedPrices.title')));
            if (onClose) {
                const close = el('button', 'pl-simulated-prices-close');
                close.type = 'button';
                close.setAttribute('aria-label', translator.translate('simulatedPrices.close'));
                close.appendChild(icon(ICONS.close));
                close.addEventListener('click', onClose);
                header.appendChild(close);
            }

            return header;
        };

        /**
         * @param {HTMLElement} container
         * @param {function} [onClose]
         */
        const build = (container, onClose) => {
            container.innerHTML = '';
            container.classList.add('pl-simulated-prices-panel');

            container.appendChild(buildHeader(onClose));
            // The introduction is for a merchant without an account (login and registration pages only).
            if (!data.connected) {
                container.appendChild(el('p', 'pl-simulated-prices-description', translator.translate('simulatedPrices.description')));
            }

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
            button.appendChild(icon(ICONS.upDown));
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
            // One page listener per panel: the sidebar is rebuilt on every opening, and a listener of an earlier
            // build would close the new list right after it opens.
            if (outsideClickHandler) {
                document.removeEventListener('click', outsideClickHandler);
            }

            outsideClickHandler = (event) => {
                if (!picker.contains(event.target)) {
                    toggleCountryList(false);
                }
            };
            document.addEventListener('click', outsideClickHandler);
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
            searchWrapper.appendChild(icon(ICONS.search));
            searchWrapper.appendChild(input);
            searchWrapper.appendChild(elements.options);

            destination.appendChild(picker);
            destination.appendChild(searchWrapper);
            controls.appendChild(destination);
            container.appendChild(controls);

            elements.chips = chips;
            elements.results = el('div', 'pl-simulated-prices-results');
            container.appendChild(elements.results);

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
            const card = el('div', 'pl-simulated-prices-card');
            const loading = el('div', 'pl-simulated-prices-message');
            loading.appendChild(el('div', 'pl-simulated-prices-spinner'));
            card.appendChild(loading);
            card.appendChild(disclaimer());
            elements.results.appendChild(card);
        };

        /**
         * The disclaimer closes every state of the card, as in the design.
         *
         * @return {HTMLElement}
         */
        const disclaimer = () => el('p', 'pl-simulated-prices-disclaimer', translator.translate('simulatedPrices.disclaimer'));

        const renderResult = () => {
            const box = elements.results;
            box.innerHTML = '';

            if (result.error || result.services.length === 0) {
                const emptyCard = el('div', 'pl-simulated-prices-card');
                const message = el('div', 'pl-simulated-prices-message');
                message.appendChild(icon(ICONS.box));
                if (!result.error || result.error === 'no_services') {
                    message.appendChild(el('strong', '', translator.translate('simulatedPrices.noServicesTitle')));
                    message.appendChild(el('span', '', translator.translate('simulatedPrices.noServicesHint')));
                } else {
                    message.appendChild(el('span', '', translator.translate(
                        ERROR_MESSAGES[result.error] || 'simulatedPrices.packlinkError'
                    )));
                }

                emptyCard.appendChild(message);
                emptyCard.appendChild(disclaimer());
                box.appendChild(emptyCard);

                return;
            }

            const card = el('div', 'pl-simulated-prices-card');
            if (result.fromPrice) {
                const note = el('div', 'pl-simulated-prices-note');
                note.appendChild(icon(ICONS.box));
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
                    const upgrade = el('a', 'pl-simulated-prices-tag pl-simulated-prices-upgrade');
                    upgrade.appendChild(icon(ICONS.plus));
                    upgrade.appendChild(document.createTextNode(translator.translate('simulatedPrices.upgrade')));
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
                toggle.appendChild(icon(expanded ? ICONS.chevronUp : ICONS.chevronDown));
                toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                toggle.addEventListener('click', () => {
                    expanded = !expanded;
                    renderResult();
                });
                card.appendChild(toggle);
            }

            card.appendChild(disclaimer());
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
        /**
         * Builds an icon from one of the fixed ICONS markups (never from data).
         *
         * @param {string} markup
         * @return {Element}
         */
        const icon = (markup) => {
            const holder = document.createElement('span');
            holder.className = 'pl-simulated-prices-icon';
            holder.setAttribute('aria-hidden', 'true');
            holder.innerHTML = markup;

            return holder;
        };

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
    /**
     * Truck icon of the "Discover our rates" button (static markup).
     */
    SimulatedPricesPanel.truckIcon = ICONS.truck;

    SimulatedPricesPanel.fromPageConfiguration = (configuration) => {
        if (!configuration || !configuration.simulatedPricesDataUrl || !configuration.simulatedPricesSearchUrl
            || !configuration.simulatedPricesLocationSearchUrl) {
            return null;
        }

        return new SimulatedPricesPanel({
            dataUrl: configuration.simulatedPricesDataUrl,
            searchUrl: configuration.simulatedPricesSearchUrl,
            locationSearchUrl: configuration.simulatedPricesLocationSearchUrl,
            initialData: configuration.simulatedPricesConfiguration || null
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

        // Everything the page had (form, registration bar) becomes the left column; the panel is the right one.
        // Both columns exist from the first paint, the panel as a placeholder of its final size.
        const main = document.createElement('div');
        main.className = 'pl-simulated-prices-page-main';
        while (page.firstChild) {
            main.appendChild(page.firstChild);
        }

        page.appendChild(main);
        const container = document.createElement('section');
        container.className = 'pl-simulated-prices-panel';
        page.appendChild(container);
        page.classList.add('pl-with-simulated-prices');
        panel.render(container, {
            introduction: true,
            onUnavailable: () => {
                page.classList.remove('pl-with-simulated-prices');
                container.remove();
            }
        });
    };

    Packlink.SimulatedPricesPanel = SimulatedPricesPanel;
})();
