<?php

namespace Logeecom\Tests\BusinessLogic;

use Logeecom\Infrastructure\Configuration;
use Logeecom\Infrastructure\Interfaces\DefaultLoggerAdapter;
use Logeecom\Infrastructure\Interfaces\Required\ShopLoggerAdapter;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\TaskExecution\Task;
use Logeecom\Infrastructure\Utility\TimeProvider;
use Logeecom\Tests\Common\TestComponents\Logger\TestShopLogger;
use Logeecom\Tests\Common\TestComponents\TestShopConfiguration;
use Logeecom\Tests\Common\TestComponents\Utility\TestTimeProvider;
use Logeecom\Tests\Common\TestServiceRegister;
use Logeecom\Tests\Common\TestComponents\Logger\TestDefaultLogger as DefaultLogger;
use PHPUnit\Framework\TestCase;

abstract class BaseSyncTest extends TestCase
{
    /**
     * @var TestShopConfiguration
     */
    protected $shopConfig;
    /**
     * @var TestShopLogger
     */
    protected $shopLogger;
    /**
     * @var array
     */
    protected $eventHistory;
    /**
     * @var Task
     */
    protected $syncTask;

    /**
     * @throws \Exception
     */
    public function setUp()
    {
        $taskInstance = $this;
        $timeProvider = new TestTimeProvider();
        $timeProvider->setCurrentLocalTime(new \DateTime());
        $this->shopConfig = new TestShopConfiguration();
        $this->shopLogger = new TestShopLogger();

        new TestServiceRegister(
            array(
                Configuration::CLASS_NAME => function () use ($taskInstance) {
                    return $taskInstance->shopConfig;
                },

                TimeProvider::CLASS_NAME => function () use ($timeProvider) {
                    return $timeProvider;
                },
                DefaultLoggerAdapter::CLASS_NAME => function () {
                    return new DefaultLogger();
                },
                ShopLoggerAdapter::CLASS_NAME => function () use ($taskInstance) {
                    return $taskInstance->shopLogger;
                },
            )
        );

        new Logger();

        $this->syncTask = $this->createSyncTaskInstance();
    }

    /**
     * @return Task
     */
    abstract protected function createSyncTaskInstance();

}