if (!window.Packlink) {
    window.Packlink = {};
}

(function (window, document, localStorage) {
    /**
     *
     * @param table Element
     * @constructor
     */
    function GridResizerService(table) {

        this.init = () => {
            if (table === null || table.dataset.id === undefined) {
                return;
            }
            const minColumnWidth = 150;
            let columns = localStorage.getItem(table.dataset.id) ? JSON.parse(localStorage.getItem(table.dataset.id)) : [];
            let headerBeingResized;

            /**
             * Handles resizing.
             *
             * @param e
             *
             * @returns {number}
             */
            const onMouseMove = (e) => requestAnimationFrame(() => {
                const width = e.clientX - (headerBeingResized !== null ? headerBeingResized.offsetLeft : 0);
                const column = columns.find(({ header }) => header === headerBeingResized);
                column.size = Math.max(minColumnWidth, width) + 'px'; // Enforce our minimum

                headerBeingResized.width = column.size;
            });

            /**
             * Cleans up and sets the new width value to localStorage.
             */
            const onMouseUp = () => {
                // Clean up.
                window.removeEventListener('mousemove', onMouseMove);
                window.removeEventListener('mouseup', onMouseUp);
                headerBeingResized.classList.remove('header--being-resized');
                headerBeingResized = null;
                if (columns !== null) {
                    localStorage.setItem(table.dataset.id, JSON.stringify(columns));
                }
            };

            /**
             * Attaches the event handlers and marks header that is being resized.
             *
             * @param target
             */
            const initResize = ({ target }) => {
                headerBeingResized = target.parentNode;
                window.addEventListener('mousemove', onMouseMove);
                window.addEventListener('mouseup', onMouseUp);
                headerBeingResized.classList.add('header--being-resized');
            };

            (() => {
                let initialColumns = [];

                document.querySelectorAll('th').forEach((header, index) => {
                    let cellWidth = header.offsetWidth + 'px';

                    if (columns.length > 0) {
                        const column = columns[index];
                        cellWidth = column.size + 'px';
                    }

                    // noinspection JSDeprecatedSymbols
                    header.width = cellWidth + 'px';
                    initialColumns.push({
                        header,
                        size: cellWidth,
                    });
                    header.innerHTML += '<span class="pl-table-resize-handle material-icons">code</span>';
                    header.querySelector('.pl-table-resize-handle').addEventListener('mousedown', initResize);
                });

                columns = initialColumns;
            })();
        }
    }

    Packlink.GridResizerService = GridResizerService;
})(window, document, window.localStorage);
