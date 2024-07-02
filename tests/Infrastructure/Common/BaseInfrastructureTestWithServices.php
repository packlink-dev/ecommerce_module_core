<?php
/** @noinspection PhpDuplicateArrayKeysInspection */

namespace Logeecom\Tests\Infrastructure\Common;

use DateTime;
use Logeecom\Infrastructure\Configuration\ConfigEntity;
use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\Logger\Interfaces\DefaultLoggerAdapter;
use Logeecom\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\Logger\LoggerConfiguration;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\Serializer\Concrete\NativeSerializer;
use Logeecom\Infrastructure\Serializer\Serializer;
use Logeecom\Infrastructure\Utility\Events\EventBus;
use Logeecom\Infrastructure\Utility\TimeProvider;
use Logeecom\Tests\Infrastructure\Common\TestComponents\Logger\TestDefaultLogger;
use Logeecom\Tests\Infrastructure\Common\TestComponents\Logger\TestShopLogger;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryStorage;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\TestRepositoryRegistry;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestShopConfiguration;
use Logeecom\Tests\Infrastructure\Common\TestComponents\Utility\TestTimeProvider;
use PHPUnit\Framework\TestCase;

/**
 * Class BaseTest.
 *
 * @package Logeecom\Tests\Infrastructure\Common
 */
abstract class BaseInfrastructureTestWithServices extends TestCase
{
    /**
     * @var TestShopConfiguration
     */
    public $shopConfig;
    /**
     * @var TestShopLogger
     */
    public $shopLogger;
    /**
     * @var TestTimeProvider
     */
    public $timeProvider;
    /**
     * @var TestDefaultLogger
     */
    public $defaultLogger;
    /**
     * @var array
     */
    public $eventHistory;
    /**
     * @var \Logeecom\Infrastructure\Serializer\Serializer
     */
    public $serializer;

    /**
     * @before
     *
     * @return void
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     */
    protected function before()
    {
        RepositoryRegistry::registerRepository(ConfigEntity::CLASS_NAME, MemoryRepository::getClassName());

        $me = $this;

        $this->timeProvider = new TestTimeProvider();
        $this->timeProvider->setCurrentLocalTime(new DateTime());
        $this->shopConfig = new TestShopConfiguration();
        $this->shopLogger = new TestShopLogger();
        $this->defaultLogger = new TestDefaultLogger();
        $this->serializer = new NativeSerializer();

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
                Serializer::CLASS_NAME => function () use ($me) {
                    return $me->serializer;
                },
            )
        );
    }

    /**
     * @after
     *
     * @return void
     */
    protected function after()
    {
        Logger::resetInstance();
        LoggerConfiguration::resetInstance();
        MemoryStorage::reset();
        TestRepositoryRegistry::cleanUp();

        parent::tearDown();
    }
}
