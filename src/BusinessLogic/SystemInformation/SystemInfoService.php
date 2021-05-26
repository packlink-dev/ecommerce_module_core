<?php

namespace Packlink\BusinessLogic\SystemInformation;

use Packlink\BusinessLogic\Http\DTO\SystemInfo;

/**
 * Interface SystemInfoService
 * @package Packlink\BusinessLogic\SystemInformation
 */
interface SystemInfoService
{
    /**
     * Returns system information.
     *
     * @return SystemInfo[]
     */
    public function getSystemDetails();
}
