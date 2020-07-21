if (!window.Packlink) {
    window.Packlink = {};
}

(function (window, document, localStorage, templateService) {
    /**
     *
     * @constructor
     */
    function GridResizerService() {
        const minColumnWidth = 170;
        const page = templateService.getMainPage();
        let columns = [];
        let headerBeingResized
        let table;

        /**
         * Initializes the given table if exists.
         * @param {HTMLTableElement} tableEl Element
         */
        this.init = (tableEl) => {
            table = tableEl;

            if (table === null || !table.id === undefined) {
                return;
            }

            columns = localStorage.getItem(table.id) ? JSON.parse(localStorage.getItem(table.id)) : [];

            let initialColumns = [];

            table.querySelectorAll('th:not(:last-child)').forEach((header, index) => {
                let cellWidth = header.offsetWidth + 'px';

                if (columns.length > 0) {
                    const column = columns[index];
                    cellWidth = column.size + 'px';
                }

                header.style.width = cellWidth;
                initialColumns.push({
                    header,
                    size: cellWidth,
                });
                header.innerHTML += '<span class="pl-table-resize-handle pl-rotate-90 material-icons">vertical_align_center</span>';
                header.querySelector('.pl-table-resize-handle').addEventListener('mousedown', initResize);
            });

            columns = initialColumns;
        }

        /**
         * Handles resizing.
         *
         * @param {Event} e
         *
         * @returns {number}
         */
        const onMouseMove = (e) => requestAnimationFrame(() => {
            page.classList.add('pl-disable-selection');
            const width = e.clientX - (headerBeingResized !== null ? headerBeingResized.offsetLeft : 0);
            const column = columns.find(({ header }) => header === headerBeingResized);
            if (!column) {
                return;
            }
            column.size = Math.max(minColumnWidth, width) + 'px'; // Enforce our minimum

            headerBeingResized.style.width = column.size;
        });

        /**
         * Cleans up and sets the new width value to localStorage.
         */
        const onMouseUp = () => {
            // Clean up.
            window.removeEventListener('mousemove', onMouseMove);
            window.removeEventListener('mouseup', onMouseUp);
            headerBeingResized = null;
            page.classList.remove('pl-disable-selection');
            if (columns !== null) {
                localStorage.setItem(table.id, JSON.stringify(columns));
            }
        };

        /**
         * Attaches the event handlers and marks header that is being resized.
         *
         * @param {Event} event
         */
        const initResize = (event) => {
            headerBeingResized = event.target.parentNode;
            window.addEventListener('mousemove', onMouseMove);
            window.addEventListener('mouseup', onMouseUp);
        };
    }

    Packlink.GridResizerService = new GridResizerService();
})(window, document, window.localStorage, Packlink.templateService);
