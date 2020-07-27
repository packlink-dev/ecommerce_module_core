if (!window.Packlink) {
    window.Packlink = {};
}

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
         * Gets the translation from the dictionary if exists.
         *
         * @param {'default' | 'current'} type
         * @param {string} group
         * @param {string} key
         * @returns {null|string}
         */
        const getTranslation = (type, group, key) => {
            if (Packlink.translations[type][group] && Packlink.translations[type][group][key]) {
                return Packlink.translations[type][group][key];
            }

            return null;
        };

        /**
         * Replaces the parameters in the given text, if any.
         *
         * @param {string} text
         * @param {[]} params
         * @return {string}
         */
        const replaceParams = (text, params) => {
            if (!params) {
                return text;
            }

            let i = 0;
            return text.replace(/%s/g, function () {
                const param = params[i] !== undefined ? params[i] : '%s';
                i++;

                return param;
            });
        };

        /**
         * Returns a translated string based on the input key and given parameters. If the string to translate
         * has parameters, the placeholder is "%s". For example: Input key %s is not valid. This method will
         * replace parameters in the order given in the params array, if any.
         *
         * @param {string} key The translation key in format "group.key".
         * @param {[]} [params] An array of parameters to be replaced in the output string.
         *
         * @return {string}
         */
        this.translate = (key, params) => {
            const keys = key.split('.');

            const result = getTranslation('current', keys[0], keys[1]) || getTranslation('default', keys[0], keys[1]);
            if (result) {
                return replaceParams(result, params);
            }

            return key;
        };

        /**
         * Replaces the translations in the given HTML code.
         *
         * @param {string} html
         * @return {string} The updated HTML.
         */
        this.translateHtml = (html) => {
            // Replace the placeholders for translations. They are in the format {$key|param1|param2}.
            let format = /{\$[.\-_A-Za-z|]+}/g;
            const me = this;

            return html.replace(format, (key) => {
                // remove the placeholder characters to get "key|param1|param2"
                key = key.substr(2, key.length - 3);
                // split parameters
                let params = key.split('|');

                return me.translate(params[0], params.slice(1)) || key;
            });
        }
    }

    Packlink.translationService = new TranslationService();
})();
