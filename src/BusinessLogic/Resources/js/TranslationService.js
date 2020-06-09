var Packlink = window.Packlink || {};

(function () {
    /**
     * A Translation service. This class turns an input key and params to the translated text.
     * The translations are used from the global Packlink.translations object. It expects two keys in this object:
     * 'current' and 'default', where 'current' holds the translations for the current language,
     * and 'default' holds the translations in the default language. The 'default' will be used as a fallback if
     * the 'current' object does not have the given entry. Both properties should be objects with the "section - key"
     * format. For example:
     *  current: {
     *      login: {
     *          title: 'The title',
     *          subtitle: 'This is the subtitle of the %s app.'
     *      },
     *      secondPage: {
     *          title: 'The second page title',
     *          description: 'Use this page to set the second thing.'
     *      }
     *  }
     *
     *  With this in mind, the translation keys are in format "section.key", for example "login.title".
     *
     * @constructor
     */
    function TranslationService() {
        /**
         * Returns a translated string based on the input key and given parameters. If the string to translate
         * has parameters, the placeholder is "%s". For example: Input key %s is not valid. This method will
         * replace parameters in the order given in the params array, if any.
         *
         * @param {string} key The translation key.
         * @param {[]} params [optional] An array of parameters to be replaced in the output string.
         */
        this.translate = function (key, params) {
            let keyParts = key.split('.');
            let result = getTranslation('current', keyParts[0], keyParts[1]) || getTranslation('default', keyParts[0], keyParts[1]);
            if (result) {
                return replaceParams(result, params);
            }

            return key;
        };

        /**
         * Gets the translation from the dictionary if exists.
         *
         * @param {string} type 'default' or 'current'
         * @param {string} key1
         * @param {string} key2
         * @returns {null|string}
         */
        function getTranslation(type, key1, key2) {
            if (Packlink.translations[type][key1] && Packlink.translations[type][key1][key2]) {
                return Packlink.translations[type][key1][key2];
            }

            return null;
        }

        function replaceParams(text, params) {
            if (!params) {
                return text;
            }

            let i = 0;
            return text.replace(/%s/g, function () {
                return params[i++] || '%s';
            });
        }
    }

    Packlink.translationService = new TranslationService();
})();
