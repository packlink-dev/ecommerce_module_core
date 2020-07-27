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
        $schema = empty($_SERVER['HTTPS']) ? 'http' : 'https';

        return "{$schema}://{$_SERVER['HTTP_HOST']}/Controllers/Index.php?controller={$controllerName}&action={$action}";
    }

    /**
     * @param $filePath
     *
     * @return string
     */
    public static function getResourceUrl($filePath = '')
    {
        $schema = empty($_SERVER['HTTPS']) ? 'http' : 'https';

        return "{$schema}://{$_SERVER['HTTP_HOST']}/Views/resources" . ($filePath ? '/' . $filePath : '');
    }

    /**
     * Returns the URL to the homepage.
     *
     * @return string
     */
    public static function getHomepage()
    {
        $schema = empty($_SERVER['HTTPS']) ? 'http' : 'https';

        return "{$schema}://{$_SERVER['HTTP_HOST']}/Views/index.php";
    }
}
