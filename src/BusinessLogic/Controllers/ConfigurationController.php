<?php

namespace Packlink\BusinessLogic\Controllers;

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\CountryLabels\Interfaces\CountryServiceInterface;
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

        /** @var CountryServiceInterface $countryService */
        $countryService = ServiceRegister::getService(CountryServiceInterface::CLASS_NAME);

        return $countryService->getLabel(strtolower($lang), 'configuration.helpUrl');
    }
}
