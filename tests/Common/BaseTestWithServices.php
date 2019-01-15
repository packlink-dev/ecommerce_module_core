<?php

namespace Logeecom\Tests\Common;

use Logeecom\Infrastructure\Configuration\ConfigEntity;
use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\Logger\Interfaces\DefaultLoggerAdapter;
use Logeecom\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\Utility\Events\EventBus;
use Logeecom\Infrastructure\Utility\TimeProvider;
use Logeecom\Tests\Common\TestComponents\Logger\TestDefaultLogger;
use Logeecom\Tests\Common\TestComponents\Logger\TestShopLogger;
use Logeecom\Tests\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Common\TestComponents\ORM\MemoryStorage;
use Logeecom\Tests\Common\TestComponents\TestShopConfiguration;
use Logeecom\Tests\Common\TestComponents\Utility\TestTimeProvider;
use PHPUnit\Framework\TestCase;

/**
 * Class BaseTest.
 *
 * @package Logeecom\Tests\Common
 */
abstract class BaseTestWithServices extends TestCase
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
     * @var TimeProvider
     */
    protected $timeProvider;
    /**
     * @var TestDefaultLogger
     */
    protected $defaultLogger;
    /**
     * @var array
     */
    protected $eventHistory;

    /**
     * @throws \Exception
     */
    protected function setUp()
    {
        RepositoryRegistry::registerRepository(ConfigEntity::CLASS_NAME, MemoryRepository::getClassName());

        $me = $this;

        $this->timeProvider = new TestTimeProvider();
        $this->timeProvider->setCurrentLocalTime(new \DateTime());
        $this->shopConfig = new TestShopConfiguration();
        $this->shopLogger = new TestShopLogger();
        $this->defaultLogger = new TestDefaultLogger();

        new TestServiceRegister(
            array(
                Configuration::CLASS_NAME => function () use ($me) {
                    return $me->shopConfig;
                },
                TimeProvider::CLASS_NAME => function () use ($me) {
                    return $me->timeProvider;
                },
                DefaultLoggerAdapter::CLASS_NAME => function () use ($me) {
                    return $me->defaultLogger;
                },
                ShopLoggerAdapter::CLASS_NAME => function () use ($me) {
                    return $me->shopLogger;
                },
                EventBus::CLASS_NAME => function () {
                    return EventBus::getInstance();
                },
            )
        );
    }

    protected function tearDown()
    {
        Logger::resetInstance();
        MemoryStorage::reset();

        parent::tearDown();
    }
}
