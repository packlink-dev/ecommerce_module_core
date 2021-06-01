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

    /**
     * Returns system information for a particular system, identified by the system ID.
     *
     * @param string $systemId
     *
     * @return SystemInfo|null
     */
    public function getSystemInfo($systemId);
}
