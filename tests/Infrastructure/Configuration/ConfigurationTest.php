<?php

namespace Logeecom\Tests\Infrastructure\logger;

use Logeecom\Tests\Infrastructure\Common\BaseInfrastructureTestWithServices;

/**
 * Class ConfigurationTest.
 *
 * @package Logeecom\Tests\Infrastructure\logger
 */
class ConfigurationTest extends BaseInfrastructureTestWithServices
{
    /**
     * Tests storing and retrieving value from config service
     */
    public function testStoringValue()
    {
        $this->shopConfig->saveMinLogLevel(5);
        $this->assertEquals(5, $this->shopConfig->getMinLogLevel());
        $this->shopConfig->saveMinLogLevel(2);
        $this->assertEquals(2, $this->shopConfig->getMinLogLevel());

        $this->shopConfig->setDefaultLoggerEnabled(false);
        $this->assertFalse($this->shopConfig->isDefaultLoggerEnabled());
        $this->shopConfig->setDefaultLoggerEnabled(true);
        $this->assertTrue($this->shopConfig->isDefaultLoggerEnabled());

        $this->shopConfig->setDebugModeEnabled(false);
        $this->assertFalse($this->shopConfig->isDebugModeEnabled());
        $this->shopConfig->setDebugModeEnabled(true);
        $this->assertTrue($this->shopConfig->isDebugModeEnabled());

        $this->shopConfig->setMaxStartedTasksLimit(45);
        $this->assertEquals(45, $this->shopConfig->getMaxStartedTasksLimit());
        $this->shopConfig->setMaxStartedTasksLimit(5);
        $this->assertEquals(5, $this->shopConfig->getMaxStartedTasksLimit());
    }

    /**
     * Test if system specific entities have system id
     */
    public function testSystemSpecific()
    {
        $this->shopConfig->setIntegrationName('new');
        $this->shopConfig->setMaxStartedTasksLimit(45);

        $config1 = $this->shopConfig->getConfigEntity('integrationName');
        $config2 = $this->shopConfig->getConfigEntity('maxStartedTasksLimit');

        $this->assertEquals($this->shopConfig->getCurrentSystemId(), $config1->getSystemId());
        $this->assertNotEquals($this->shopConfig->getCurrentSystemId(), $config2->getSystemId());
    }
}
