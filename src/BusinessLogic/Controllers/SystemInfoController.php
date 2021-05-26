<?php

namespace Packlink\BusinessLogic\Controllers;

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Http\DTO\SystemInfo;
use Packlink\BusinessLogic\SystemInformation\SystemInfoService;

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
        return $this->systemInfoService->getSystemDetails();
    }
}
