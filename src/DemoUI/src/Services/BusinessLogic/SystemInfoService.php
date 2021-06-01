<?php

namespace Packlink\DemoUI\Services\BusinessLogic;

use Packlink\BusinessLogic\Http\DTO\SystemInfo;
use Packlink\BusinessLogic\SystemInformation\SystemInfoService as SystemInfoServiceInterface;

/**
 * Class SystemInfoService
 *
 * @package Packlink\DemoUI\Services\BusinessLogic
 */
class SystemInfoService implements SystemInfoServiceInterface
{
    /**
     * Returns system information.
     *
     * @return SystemInfo[]
     */
    public function getSystemDetails()
    {
        return array(SystemInfo::fromArray(array(
            'system_id' => 'test',
            'system_name' => 'Demo UI',
            'currencies' => array('EUR'),
        )));
    }

    /**
     * Returns system information for a particular system, identified by the system ID.
     *
     * @param string $systemId
     *
     * @return SystemInfo|null
     */
    public function getSystemInfo($systemId)
    {
        return SystemInfo::fromArray(array(
            'system_id' => 'test',
            'system_name' => 'Demo UI',
            'currencies' => array('EUR'),
        ));
    }
}