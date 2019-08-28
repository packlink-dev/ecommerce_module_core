var Packlink = window.Packlink || {};

(function () {
    /**
     * AutoTest controller constructor.
     *
     * @param {string} startTestUrl Url to start the auto test.
     * @param {string} checkStatusUrl Url to check the status of the auto test.
     *
     * @constructor
     */
    function AutoTestController(startTestUrl, checkStatusUrl) {
        let startButton = document.getElementById('pl-auto-test-start');
        if (startButton) {
            startButton.addEventListener('click', startTestButtonClicked, true);
        }

        /**
         * Event handler for start button click event.
         *
         * @param event
         */
        function startTestButtonClicked(event) {
            let logPanel = document.getElementById('pl-auto-test-log-panel');

            // hide button
            event.target.style.display = 'none';
            logPanel.style.display = 'block';
            Packlink.utilityService.showSpinner();

            Packlink.ajaxService.get(
                startTestUrl,
                /** @param {{success: boolean, error: string}} response */
                function startTest(response) {
                    if (response.success) {
                        setTimeout(updateStatus, 1000);
                    } else {
                        document.getElementById('pl-auto-test-log-panel').innerHTML = response.error;
                    }
                }
            );
        }

        /**
         * Gets the current status of the test and updates log messages in the log panel.
         */
        function updateStatus() {
            Packlink.ajaxService.get(
                checkStatusUrl,
                /** @param {{logs: array, finished: boolean}} response */
                function (response) {
                    let logPanel = document.getElementById('pl-auto-test-log-panel');

                    logPanel.innerHTML = '';
                    for (let log of response.logs) {
                        logPanel.innerHTML += writeLogMessage(log);
                    }

                    logPanel.scrollIntoView();
                    logPanel.scrollTop = logPanel.scrollHeight;
                    response.finished ? finishTest(response) : setTimeout(updateStatus, 1000);
                }
            );
        }

        /**
         * Gets message from log object.
         *
         * @param {object} log See LogData entity for object's properties.
         * @returns {string} Formatted HTML for a single message.
         */
        function writeLogMessage(log) {
            let date = new Date(log.timestamp),
                message = '<div class="pl-auto-test-log">' +
                    '<span class="log-date">'
                    + Packlink.utilityService.pad(date.getHours(), 2, '0') + ':'
                    + Packlink.utilityService.pad(date.getMinutes(), 2, '0') + ':'
                    + Packlink.utilityService.pad(date.getSeconds(), 2, '0') + ':'
                    + '</span>' +
                    '<span class="log-message">' + log.message + '</span>';

            if (log.context) {
                message += '<pre class="log-message-context">' + JSON.stringify(log.context, null, 2) + '</pre>';
            }

            return message + '</div>';
        }

        /**
         * Displays appropriate panels when the test finished.
         *
         * @param {object} response
         */
        function finishTest(response) {
            Packlink.utilityService.hideSpinner();
            document.getElementById('pl-spinner-box').style.display = 'none';

            if (response.error) {
                document.getElementById('pl-flash-message-fail').style.display = 'flex';
            } else {
                document.getElementById('pl-flash-message-success').style.display = 'flex';
            }

            let donePanel = document.getElementById('pl-auto-test-done');
            donePanel.style.display = 'block';
            donePanel.scrollIntoView();
        }
    }

    Packlink.AutoTestController = AutoTestController;
})();
