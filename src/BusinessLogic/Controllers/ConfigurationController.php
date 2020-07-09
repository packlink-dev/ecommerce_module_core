<?php

namespace Packlink\BusinessLogic\Controllers;

use Packlink\DemoUI\Services\Integration\UrlService;

/**
 * Class ConfigurationController.
 *
 * @package Packlink\BusinessLogic\Controllers
 */
class ConfigurationController
{
    /**
     * List of help URLs for different country codes.
     *
     * @var array
     */
    private static $helpUrls = array(
        'EN' => 'https://support-pro.packlink.com/hc/en-gb',
        'ES' => 'https://support-pro.packlink.com/hc/es-es',
        'DE' => 'https://support-pro.packlink.com/hc/de',
        'FR' => 'https://support-pro.packlink.com/hc/fr-fr',
        'IT' => 'https://support-pro.packlink.com/hc/it',
    );

    /**
     * @return mixed|string
     */
    public function getHelpLink()
    {
        $lang = UrlService::getUrlLocaleKey();

        if (!array_key_exists($lang, static::$helpUrls)) {
            $lang = 'EN';
        }

        return static::$helpUrls[$lang];
    }
}
