<?php

namespace Packlink\DemoUI\Services\Integration;

/**
 * Class UrlService
 *
 * @package Packlink\DemoUI\Repository
 */
class UrlService
{
    /**
     * @param $controllerName
     * @param $action
     *
     * @return string
     */
    public static function getEndpointUrl($controllerName, $action)
    {
        return static::getSchema() . "://{$_SERVER['HTTP_HOST']}/Controllers/Index.php?controller={$controllerName}&action={$action}";
    }

    /**
     * @param $filePath
     *
     * @return string
     */
    public static function getResourceUrl($filePath = '')
    {
        $brandPlatformCode = getenv('PL_PLATFORM');

        return static::getSchema() . "://{$_SERVER['HTTP_HOST']}/Views/$brandPlatformCode/resources" . ($filePath ? '/' . $filePath : '');
    }

    /**
     * Returns the URL to the homepage.
     *
     * @return string
     */
    public static function getHomepage()
    {
        $brandPlatformCode = getenv('PL_PLATFORM');

        return static::getSchema() . "://{$_SERVER['HTTP_HOST']}/Views/$brandPlatformCode/index.php";
    }

    /**
     * Resolves the request schema, honoring the X-Forwarded-Proto header set by
     * reverse proxies (e.g. ngrok) that terminate TLS in front of this dev server -
     * $_SERVER['HTTPS'] alone is never set in that case, which would otherwise
     * generate http:// URLs for a page actually served over https://.
     *
     * @return string 'http' or 'https'
     */
    private static function getSchema()
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            return strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https' ? 'https' : 'http';
        }

        return empty($_SERVER['HTTPS']) ? 'http' : 'https';
    }
}
