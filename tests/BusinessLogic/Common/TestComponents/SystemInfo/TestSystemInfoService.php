<?php

namespace Logeecom\Tests\BusinessLogic\Common\TestComponents\SystemInfo;

use Packlink\BusinessLogic\Http\DTO\SystemInfo;
use Packlink\BusinessLogic\SystemInformation\SystemInfoService as SystemInfoServiceInterface;

/**
 * Class TestSystemInfoService
 *
 * @package BusinessLogic\Common\TestComponents\SystemInfo
 */
class TestSystemInfoService implements SystemInfoServiceInterface
{
    /**
     * Returns system information.
     *
     * @return SystemInfo[]
     */
    public function getSystemDetails()
    {
        return array(
            SystemInfo::fromArray(array(
                'system_id' => 'test',
                'system_name' => 'Unit tests',
                'currencies' => array('EUR'),
            )),
            SystemInfo::fromArray(array(
                'system_id' => 'test1',
                'system_name' => 'Unit tests 1',
                'currencies' => array('GBP'),
            ))
        );
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
            'system_id' => $systemId,
            'system_name' => 'Unit tests',
            'currencies' => $systemId !== 'invalid' ? array('EUR', 'GBP') : array('GBP'),
        ));
    }
}
