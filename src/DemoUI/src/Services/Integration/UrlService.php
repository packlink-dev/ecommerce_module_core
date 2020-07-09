<?php

namespace Packlink\DemoUI\Services\Integration;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\ServiceRegister;

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
    public static function getResourceUrl($filePath)
    {
        $schema = empty($_SERVER['HTTPS']) ? 'http' : 'https';

        return "{$schema}://{$_SERVER['HTTP_HOST']}/Views/resources/{$filePath}";
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

    /**
     * Returns locale for URLs in uppercase format.
     *
     * @return string
     */
    public static function getUrlLocaleKey()
    {
        $locale = 'EN';

        /** @var \Packlink\BusinessLogic\Configuration $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        $userInfo = $configService->getUserInfo();
        $currentLang = $configService::getCurrentLanguage();

        if ($userInfo !== null && in_array($userInfo->country, array('ES', 'DE', 'FR', 'IT'), true)) {
            $locale = $userInfo->country;
        } elseif (in_array(strtoupper($currentLang), array('ES', 'DE', 'FR', 'IT'), true)) {
            $locale = strtoupper($currentLang);
        }

        return $locale;
    }
}
