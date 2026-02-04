<?php

namespace Logeecom\Tests\Infrastructure\logger;

use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\AsyncProcessUrlProviderInterface;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerConfigInterface;
use Logeecom\Infrastructure\TaskExecution\TaskRunnerConfig;
use Logeecom\Tests\Infrastructure\Common\BaseInfrastructureTestWithServices;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestAsyncProcessUrlProvider;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;

/**
 * Class ConfigurationTest.
 *
 * @package Logeecom\Tests\Infrastructure\logger
 */
class ConfigurationTest extends BaseInfrastructureTestWithServices
{
    /**
     * @before
     *
     * @throws \Exception
     */
    public function before()
    {
        parent::before();


        TestServiceRegister::registerService(
            AsyncProcessUrlProviderInterface::CLASS_NAME,
            function () {
                return new TestAsyncProcessUrlProvider();
            }
        );

        TestServiceRegister::registerService(
            TaskRunnerConfigInterface::CLASS_NAME,
            function () {
                $config = ServiceRegister::getService(\Logeecom\Infrastructure\Configuration\Configuration::CLASS_NAME);
                $urlProvider = ServiceRegister::getService(AsyncProcessUrlProviderInterface::CLASS_NAME);

                return new TaskRunnerConfig($config, $urlProvider);
            }
        );

    }
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
    }

    /**
     * Test if system specific entities have system id
     */
    public function testSystemSpecific()
    {
        $taskRunnerConfig = TestServiceRegister::getService(TaskRunnerConfigInterface::CLASS_NAME);

        $this->shopConfig->setIntegrationName('new');
        $taskRunnerConfig->setMaxStartedTasksLimit(45);

        $config1 = $this->shopConfig->getConfigEntity('integrationName');
        $config2 = $this->shopConfig->getConfigEntity('maxStartedTasksLimit');

        $this->assertEquals($this->shopConfig->getCurrentSystemId(), $config1->getSystemId());
        $this->assertNotEquals($this->shopConfig->getCurrentSystemId(), $config2->getSystemId());
    }
}
