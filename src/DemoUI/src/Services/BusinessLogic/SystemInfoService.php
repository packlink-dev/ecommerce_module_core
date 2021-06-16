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
     * @var bool
     */
    private static $isMultistore = false;

    /**
     * Returns system information.
     *
     * @return SystemInfo[]
     */
    public function getSystemDetails()
    {
        $systems = array(
            SystemInfo::fromArray(array(
                'system_id' => 'test',
                'system_name' => 'Demo UI',
                'currencies' => array('GBP'),
            )
        ));

        if (self::$isMultistore) {
            $systems[] = SystemInfo::fromArray(array(
                'system_id' => 'test 2',
                'system_name' => 'Demo UI 2',
                'currencies' => array('EUR'),
            ));
        }

        return $systems;
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

    /**
     * Sets multistore.
     *
     * @param $isMultistore
     */
    public function setMultistore($isMultistore)
    {
        self::$isMultistore = $isMultistore;
    }
}