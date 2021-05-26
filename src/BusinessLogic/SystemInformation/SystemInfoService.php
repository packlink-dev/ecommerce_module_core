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
     * Class name.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * Returns system information.
     *
     * @return SystemInfo[]
     */
    public function getSystemDetails();
}
