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
     * @var bool
     */
    private static $isMultistore = false;
    /**
     * @var bool
     */
    private static $isInvalid = false;

    /**
     * Returns system information.
     *
     * @return SystemInfo[]
     */
    public function getSystemDetails()
    {
        $systems = array(SystemInfo::fromArray(array(
            'system_id' => 'test',
            'system_name' => 'Unit tests',
            'currencies' => self::$isInvalid ?  array('GBP') : array('EUR'),
        )));

        if (self::$isMultistore) {
            $systems[] = SystemInfo::fromArray(array(
                'system_id' => 'test1',
                'system_name' => 'Unit tests 1',
                'currencies' => array('GBP'),
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
            'system_id' => $systemId,
            'system_name' => 'Unit tests',
            'currencies' => $systemId !== 'invalid' ? array('EUR', 'GBP') : array('GBP'),
        ));
    }

    /**
     * Sets multistore.
     *
     * @param bool $isMultistore
     */
    public function setMultistore($isMultistore)
    {
        self::$isMultistore = $isMultistore;
    }

    /**
     * Sets multistore.
     *
     * @param bool $isInvalid
     */
    public function setInvalid($isInvalid)
    {
        self::$isInvalid = $isInvalid;
    }
}
