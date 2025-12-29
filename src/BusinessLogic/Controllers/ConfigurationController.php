<?php

namespace Packlink\BusinessLogic\Controllers;

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\CountryLabels\Interfaces\CountryService;
use Packlink\BusinessLogic\Utility\UrlService;

/**
 * Class ConfigurationController.
 *
 * @package Packlink\BusinessLogic\Controllers
 */
class ConfigurationController
{
    /**
     * @return string
     */
    public function getHelpLink()
    {
        $lang = UrlService::getUrlLocaleKey();

        /** @var CountryService $countryService */
        $countryService = ServiceRegister::getService(CountryService::CLASS_NAME);

        return $countryService->getLabel(strtolower($lang), 'configuration.helpUrl');
    }
}
