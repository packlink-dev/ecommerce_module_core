<?php

namespace Packlink\BusinessLogic\Controllers;

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Http\DTO\SystemInfo;
use Packlink\BusinessLogic\SystemInformation\SystemInfoService;
use Packlink\BusinessLogic\Utility\CurrencySymbolService;

/**
 * Class SystemInfoController
 * @package Packlink\BusinessLogic\Controllers
 */
class SystemInfoController
{
    /**
     * @var SystemInfoService
     */
    private $systemInfoService;

    /**
     * SystemInfoController constructor.
     */
    public function __construct()
    {
        $this->systemInfoService = ServiceRegister::getService(SystemInfoService::CLASS_NAME);
    }

    /**
     * Returns list of system info details.
     *
     * @return SystemInfo[]
     */
    public function get()
    {
        $systemDetails = $this->systemInfoService->getSystemDetails();
        foreach ($systemDetails as $systemInfo) {
            $systemInfo->symbols = $this->getCurrencySymbols($systemInfo->currencies);
        }

        return $systemDetails;
    }

    /**
     * Returns currency symbol for the provided currency codes.
     *
     * @param array $currencyCodes
     *
     * @return array
     */
    protected function getCurrencySymbols($currencyCodes)
    {
        $symbols = array();

        foreach ($currencyCodes as $currencyCode) {
            $symbols[$currencyCode] = CurrencySymbolService::getCurrencySymbol($currencyCode);
        }

        return $symbols;
    }
}
