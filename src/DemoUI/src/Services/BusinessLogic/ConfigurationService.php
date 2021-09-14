<?php

namespace Packlink\DemoUI\Services\BusinessLogic;

use Packlink\BusinessLogic\Configuration;

/**
 * Class ConfigurationService
 *
 * @package Packlink\PacklinkPro\Services\BusinessLogic
 */
class ConfigurationService extends Configuration
{
    /**
     * Max inactivity period for a task in seconds
     */
    const MAX_TASK_INACTIVITY_PERIOD = 60;

    /**
     * Default HTTP method to use for async call.
     */
    const ASYNC_CALL_METHOD = 'GET';

    /**
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;

    /**
     * Gets max inactivity period for a task in seconds.
     * After inactivity period is passed, system will fail such task as expired.
     *
     * @return int Max task inactivity period in seconds if set; otherwise, self::MAX_TASK_INACTIVITY_PERIOD.
     */
    public function getMaxTaskInactivityPeriod()
    {
        return parent::getMaxTaskInactivityPeriod() ?: self::MAX_TASK_INACTIVITY_PERIOD;
    }

    /**
     * Retrieves integration name.
     *
     * @return string Integration name.
     */
    public function getIntegrationName()
    {
        return 'DemoUI';
    }

    /**
     * Returns current system identifier.
     *
     * @return string Current system identifier.
     */
    public function getCurrentSystemId()
    {
        return '';
    }

    /**
     * Returns async process starter url, always in http.
     *
     * @param string $guid Process identifier.
     *
     * @return string Formatted URL of async process starter endpoint.
     */
    public function getAsyncProcessUrl($guid)
    {
        return '';
    }

    /**
     * Returns web-hook callback URL for current system.
     *
     * @return string Web-hook callback URL.
     */
    public function getWebHookUrl()
    {
        return '';
    }

    /**
     * Returns order draft source.
     *
     * @return string Order draft source.
     */
    public function getDraftSource()
    {
        return 'module_DemoUI';
    }

    /**
     * Gets the current version of the module/integration.
     *
     * @return string The version number.
     */
    public function getModuleVersion()
    {
        return '1.0.0';
    }

    /**
     * Gets the name of the integrated e-commerce system.
     * This name is related to Packlink API which can be different from the official system name.
     *
     * @return string The e-commerce name.
     */
    public function getECommerceName()
    {
        return 'DemoUI';
    }

    /**
     * Gets the current version of the integrated e-commerce system.
     *
     * @return string The version number.
     */
    public function getECommerceVersion()
    {
        return '1.0.0';
    }

    /**
     * Determines whether the configuration entry is system specific.
     *
     * @param string $name Configuration entry name.
     *
     * @return bool
     */
    protected function isSystemSpecific($name)
    {
        return false;
    }
}
