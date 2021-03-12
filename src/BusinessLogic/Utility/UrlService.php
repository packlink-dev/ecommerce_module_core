<?php

namespace Packlink\BusinessLogic\Utility;

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
        $currentLang = $configService::getUICountryCode();

        if ($userInfo !== null && in_array($userInfo->country, array('ES', 'DE', 'FR', 'IT'), true)) {
            $locale = $userInfo->country;
        } elseif (in_array(strtoupper($currentLang), array('ES', 'DE', 'FR', 'IT'), true)) {
            $locale = strtoupper($currentLang);
        }

        return $locale;
    }
}
