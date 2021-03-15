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

        if ($userInfo !== null) {
            $locale = $userInfo->country;
        } elseif ($currentLang) {
            $locale = strtoupper($currentLang);
        }

        return $locale;
    }
}
