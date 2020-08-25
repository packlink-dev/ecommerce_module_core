if (!window.Packlink) {
    window.Packlink = {};
}

(function (window, document, localStorage) {
    /**
     *
     * @constructor
     */
    function GridResizerService() {
        const templateService = Packlink.templateService,
            page = templateService.getComponent('pl-page');

        let columns = [],
            table,
            pageX,
            currentColumn,
            nextColumn,
            currentColumnWidth,
            nextColumnWidth;

        /**
         * Initializes the given table if exists.
         * @param {HTMLTableElement} tableEl Element
         */
        this.init = (tableEl) => {
            table = tableEl;

            if (table === null || !table.id) {
                return;
            }

            columns = localStorage.getItem(table.id) ? JSON.parse(localStorage.getItem(table.id)) : [];

            let initialColumns = [];
            const headers = table.querySelectorAll('th:not(:last-child)');

            headers.forEach((header, index) => {
                let cellWidth = header.offsetWidth + 'px';

                if (columns.length > 0) {
                    const column = columns[index];
                    cellWidth = column.size;
                }

                header.style.width = cellWidth;
                initialColumns.push({
                    header,
                    size: cellWidth,
                });

                if (index === headers.length - 1) {
                    return;
                }

                const resizeHandler = header.querySelector('.pl-table-resize-handle');
                if (resizeHandler) {
                    resizeHandler.parentNode.removeChild(resizeHandler);
                }
                header.innerHTML += '<span class="pl-table-resize-handle material-icons">vertical_align_center</span>';

                header.addEventListener('mousedown', onMouseDown);
                document.addEventListener('mousemove', onMouseMove);
                document.addEventListener('mouseup',onMouseUp );
            });

            columns = initialColumns;
        }

        /**
         * Handles mouse down event.
         *
         * @param {MouseEvent} event
         */
        const onMouseDown = (event) => {
            currentColumn = event.target.parentElement;
            nextColumn = currentColumn.nextElementSibling;
            pageX = event.pageX;

            currentColumnWidth = currentColumn.offsetWidth;
            if (nextColumn) {
                nextColumnWidth = nextColumn.offsetWidth;
            }
        };

        /**
         * Handles mouse move event.
         *
         * @param {MouseEvent} event
         */
        const onMouseMove = (event) => {
            if (currentColumn) {
                page.classList.add('pl-disable-selection');

                const diffX = event.pageX - pageX;

                if (nextColumn) {
                    const next = columns.find(({ header }) => header === nextColumn);
                    next.size = (nextColumnWidth - (diffX)) + 'px';
                    nextColumn.style.width = next.size;
                }

                const current = columns.find(({ header }) => header === currentColumn);
                current.size = (currentColumnWidth + diffX) + 'px';
                currentColumn.style.width = current.size;
            }
        };

        const onMouseUp = () => {
            page.classList.remove('pl-disable-selection');
            localStorage.setItem(table.id, JSON.stringify(columns));
            currentColumn = undefined;
            nextColumn = undefined;
            pageX = undefined;
            nextColumnWidth = undefined;
            currentColumnWidth = undefined;
        };
    }

    Packlink.GridResizerService = new GridResizerService();
})(window, document, window.localStorage);
