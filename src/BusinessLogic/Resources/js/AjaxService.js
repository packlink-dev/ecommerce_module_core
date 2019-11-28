var Packlink = window.Packlink || {};

(function () {
    /**
     * Ajax service. Methods are make public so that they could be overridden in the integrations that
     * require different mechanisms for AJAX requests.
     *
     * @constructor
     */
    function AjaxService() {
        /**
         * Performs GET ajax request.
         *
         * @param {string} url
         * @param {function} [onSuccess]
         * @param {function} [onError]
         */
        this.get = function (url, onSuccess, onError) {
            this.call('GET', url, {}, onSuccess, onError);
        };

        /**
         * Performs POST ajax request.
         *
         * @note You can not post data that has fields with special values such as infinity, undefined etc.
         *
         * @param {string} url
         * @param {object} data
         * @param {function} [onSuccess]
         * @param {function} [onError]
         */
        this.post = function (url, data, onSuccess, onError) {
            this.call('POST', url, data, onSuccess, onError);
        };

        /**
         * Performs ajax call.
         *
         * @param {string} method 'GET' or 'POST'.
         * @param {string} url
         * @param {object} data
         * @param {function} [onSuccess]
         * @param {function} [onError]
         */
        this.call = function (method, url, data, onSuccess, onError) {
            let request = getRequest();

            url = url.replace('https:', '');
            url = url.replace('http:', '');

            request.open(method, url, true);

            request.onreadystatechange = function () {
                // "this" is XMLHttpRequest
                if (this.readyState === 4) {
                    if (this.status >= 200 && this.status < 300) {
                        onSuccess(JSON.parse(this.responseText || '{}'));
                    } else {
                        if (typeof onError !== 'undefined') {
                            let response = this.responseText;
                            try {
                                response = JSON.parse(this.responseText || '{}');
                            } catch (e) {
                            }

                            onError(response);
                        }
                    }
                }
            };

            if (method === 'POST') {
                this.internalPerformPost(request, data);
            } else {
                request.send();
            }
        };

        /**
         * Extension point for executing the POST request with the given data.
         *
         * @param {XMLHttpRequest | ActiveXObject} request
         * @param {object} data
         */
        this.internalPerformPost = function (request, data) {
            request.setRequestHeader('Content-Type', 'application/json');
            request.send(JSON.stringify(data));
        };

        /**
         * Creates instance of request.
         *
         * @return {XMLHttpRequest | ActiveXObject}
         */
        function getRequest() {
            let versions = [
                    'MSXML2.XmlHttp.6.0',
                    'MSXML2.XmlHttp.5.0',
                    'MSXML2.XmlHttp.4.0',
                    'MSXML2.XmlHttp.3.0',
                    'MSXML2.XmlHttp.2.0',
                    'Microsoft.XmlHttp'
                ],
                xhr;

            if (typeof XMLHttpRequest !== 'undefined') {
                return new XMLHttpRequest();
            }

            for (let version of versions) {
                try {
                    xhr = new ActiveXObject(version);
                    break;
                } catch (e) {
                }
            }

            return xhr;
        }
    }

    Packlink.ajaxService = new AjaxService();
})();