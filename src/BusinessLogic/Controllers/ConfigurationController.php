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
     * @return mixed|string
     */
    public function getHelpLink()
    {
        $lang = UrlService::getUrlLocaleKey();

        /** @var CountryService $countryService */
        $countryService = ServiceRegister::getService(CountryService::CLASS_NAME);

        return $countryService->getLabels(strtolower($lang), 'configuration.helpUrl');
    }
}
