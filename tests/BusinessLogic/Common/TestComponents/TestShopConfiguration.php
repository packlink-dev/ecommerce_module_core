<?php

namespace Logeecom\Tests\BusinessLogic\Common\TestComponents;

use Packlink\BusinessLogic\Configuration;

class TestShopConfiguration extends Configuration
{
    private $callbackUrl = 'https://some-shop.test/callback?a=1&b=abc';
    private $servicePointEnabled = true;
    /**
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;

    public function __construct()
    {
        parent::__construct();

        static::$instance = $this;
    }

    /**
     * Returns current system identifier.
     *
     * @return string Current system identifier.
     */
    public function getCurrentSystemId()
    {
        return 'test';
    }

    /**
     * Returns callback url, if it is sub-shop it should return its specific url.
     * Urls must have "token" query parameter, and should have "shop_code" if system supports multiple shops.
     *
     * @return string
     */
    public function getCallbackUrl()
    {
        return $this->callbackUrl;
    }

    /**
     * Returns service point enabled flag
     *
     * @return bool
     */
    public function isServicePointEnabled()
    {
        return $this->servicePointEnabled;
    }

    /**
     * Sets service point enabled flag
     *
     * @param $enabled
     */
    public function setServicePointEnabled($enabled)
    {
        $this->servicePointEnabled = $enabled;
    }

    /**
     * Returns scheduler time threshold between checks
     *
     * @return int
     */
    public function getSchedulerTimeThreshold()
    {
        return 60;
    }

    /**
     * Returns scheduler queue name
     *
     * @return string
     */
    public function getSchedulerQueueName()
    {
        return 'Test Scheduler Queue';
    }

    /**
     * Returns web-hook callback URL for current system.
     *
     * @return string Web-hook callback URL.
     */
    public function getWebHookUrl()
    {
        return 'https://example.com';
    }

    /**
     * Returns order draft source.
     *
     * @return string Order draft source.
     */
    public function getDraftSource()
    {
        return 'module_unknown';
    }

    /**
     * @inheritDoc
     */
    public function getModuleVersion()
    {
        return '1.2.3';
    }

    /**
     * @inheritDoc
     */
    public function getECommerceName()
    {
        return 'test_system';
    }

    /**
     * @inheritDoc
     */
    public function getECommerceVersion()
    {
        return '3.2.1';
    }

    /**
     * Retrieves integration name.
     *
     * @return string Integration name.
     */
    public function getIntegrationName()
    {
        return $this->getConfigValue('integrationName', 'test-system');
    }

    /**
     * Sets integration name.
     *
     * @param string $name Integration name.
     */
    public function setIntegrationName($name)
    {
        $this->saveConfigValue('integrationName', $name);
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
        return str_replace('https://', 'http://', $this->callbackUrl . '&guid=' . $guid);
    }
}
