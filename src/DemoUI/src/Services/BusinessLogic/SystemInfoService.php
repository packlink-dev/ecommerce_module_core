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
}