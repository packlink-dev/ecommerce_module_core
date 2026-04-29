if (!window.Packlink) {
    window.Packlink = {};
}

(function () {
    /**
     * Browser PDF print utility. Loads a PDF in a hidden iframe and triggers
     * the browser's native print dialog. Falls back to opening the PDF in a
     * new tab if printing through the iframe fails (cross-origin or blocked).
     *
     * The iframe URL must be same-origin -- platforms are expected to expose
     * a server-side proxy endpoint for external PDFs.
     *
     * @constructor
     */
    function PrintService() {
        /**
         * Prints a PDF by loading it in a hidden iframe and triggering
         * the browser's native print dialog.
         *
         * @param {string} url Same-origin URL to the PDF.
         * @param {function} [onComplete] Called after the print dialog is dismissed.
         */
        this.printPdf = function (url, onComplete) {
            const iframe = document.createElement('iframe');
            iframe.style.position = 'fixed';
            iframe.style.right = '0';
            iframe.style.bottom = '0';
            iframe.style.width = '0';
            iframe.style.height = '0';
            iframe.style.border = 'none';
            iframe.src = url;

            let teardownCalled = false;
            const teardown = function () {
                if (teardownCalled) {
                    return;
                }
                teardownCalled = true;
                if (iframe.parentNode) {
                    iframe.parentNode.removeChild(iframe);
                }
                if (typeof onComplete === 'function') {
                    onComplete();
                }
            };

            iframe.onload = function () {
                const contentWindow = iframe.contentWindow;
                try {
                    contentWindow.addEventListener('afterprint', teardown);
                } catch (e) {
                    // Some PDF-viewer iframes block addEventListener;
                    // the safety timeout below will still reclaim the iframe.
                }
                try {
                    contentWindow.focus();
                    contentWindow.print();
                } catch (e) {
                    window.open(url, '_blank');
                    teardown();
                    return;
                }

                setTimeout(teardown, 60000);
            };

            document.body.appendChild(iframe);
        };
    }

    Packlink.printService = new PrintService();
})();