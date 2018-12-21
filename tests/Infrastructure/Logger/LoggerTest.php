<?php

namespace Logeecom\Tests\Infrastructure\logger;

use Logeecom\Tests\Common\TestServiceRegister;
use PHPUnit\Framework\TestCase;
use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\Interfaces\DefaultLoggerAdapter;
use Logeecom\Infrastructure\Configuration as ConfigInterface;
use Logeecom\Infrastructure\Interfaces\required\ShopLoggerAdapter;
use Logeecom\Infrastructure\Logger\Configuration;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\Utility\TimeProvider;
use Logeecom\Tests\Common\TestComponents\TestShopConfiguration;
use Logeecom\Tests\Common\TestComponents\Logger\TestShopLogger;
use Logeecom\Tests\Common\TestComponents\Logger\TestDefaultLogger;
use Logeecom\Tests\Common\TestComponents\TestHttpClient;

class LoggerTest extends TestCase
{
    /**
     * @var TimeProvider
     */
    private $timeProvider;
    /**
     * @var TestDefaultLogger
     */
    private $defaultLogger;
    /**
     * @var TestShopLogger
     */
    private $shopLogger;
    /**
     * @var TestShopConfiguration
     */
    private $shopConfiguration;
    /**
     * @var TestHttpClient
     */
    private $httpClient;

    protected function setUp()
    {
        Configuration::resetInstance();
        $this->defaultLogger = new TestDefaultLogger();
        $this->shopLogger = new TestShopLogger();
        $this->httpClient = new TestHttpClient();
        $this->timeProvider = TimeProvider::getInstance();
        $this->shopConfiguration = new TestShopConfiguration();
        $this->shopConfiguration->setIntegrationName('Shop1');
        $this->shopConfiguration->setDefaultLoggerEnabled(true);

        $componentInstance = $this;

        new TestServiceRegister(
            array(
                TimeProvider::CLASS_NAME => function () use ($componentInstance) {
                    return $componentInstance->timeProvider;
                },
                DefaultLoggerAdapter::CLASS_NAME => function () use ($componentInstance) {
                    return $componentInstance->defaultLogger;
                },
                ShopLoggerAdapter::CLASS_NAME => function () use ($componentInstance) {
                    return $componentInstance->shopLogger;
                },
                ConfigInterface::CLASS_NAME => function () use ($componentInstance) {
                    return $componentInstance->shopConfiguration;
                },
                HttpClient::CLASS_NAME => function () use ($componentInstance) {
                    return $componentInstance->httpClient;
                },
            )
        );

        new Logger();
    }

    /**
     * Test if error log level is passed to shop logger
     */
    public function testErrorLogLevelIsPassed()
    {
        Logger::logError('Some data');
        $this->assertEquals(0, $this->shopLogger->data->getLogLevel(), 'Log level for error call must be 0.');
    }

    /**
     * Test if warning log level is passed to shop logger
     */
    public function testWarningLogLevelIsPassed()
    {
        Logger::logWarning('Some data');
        $this->assertEquals(1, $this->shopLogger->data->getLogLevel(), 'Log level for warning call must be 1.');
    }

    /**
     * Test if info log level is passed to shop logger
     */
    public function testInfoLogLevelIsPassed()
    {
        Logger::logInfo('Some data');
        $this->assertEquals(2, $this->shopLogger->data->getLogLevel(), 'Log level for info call must be 2.');
    }

    /**
     * Test if debug log level is passed to shop logger
     */
    public function testDebugLogLevelIsPassed()
    {
        Logger::logDebug('Some data');
        $this->assertEquals(3, $this->shopLogger->data->getLogLevel(), 'Log level for debug call must be 3.');
    }

    /**
     * Test if log data is sent to shop logger
     */
    public function testLogMessageIsSent()
    {
        Logger::logInfo('Some data');
        $this->assertEquals('Some data', $this->shopLogger->data->getMessage(), 'Log message must be sent.');
    }

    /**
     * Test if log data is sent to shop logger
     */
    public function testLogComponentIsSent()
    {
        Logger::logInfo('Some data');
        $this->assertEquals('Core', $this->shopLogger->data->getComponent(), 'Log component must be sent');
    }

    /**
     * Test if log data is sent to shop logger
     */
    public function testLogIntegrationIsSent()
    {
        Logger::logInfo('Some data');
        $this->assertEquals('Shop1', $this->shopLogger->data->getIntegration(), 'Log integration must be sent');
    }

    /**
     * Test if message will not be logged to default logger when it is off
     */
    public function testNotLoggingToDefaultLoggerWhenItIsOff()
    {
        Configuration::setDefaultLoggerEnabled(false);
        Logger::logInfo('Some data');
        $this->assertFalse($this->httpClient->calledAsync, 'Default logger should not send log when it is off.');
    }

    /**
     * Test if message will be logged to default logger when it is on
     */
    public function testLoggingToDefaultLoggerWhenItIsOn()
    {
        Configuration::setDefaultLoggerEnabled(true);
        Logger::logInfo('Some data');
        $this->assertTrue($this->httpClient->calledAsync, 'Default logger should send log when it is on.');
    }

    /**
     * Test if message logged to default logger will have timestamp set in milliseconds
     */
    public function testLoggingToDefaultLoggerTimestamp()
    {
        $timeSeconds = time();
        Logger::logInfo('Some data');
        $this->assertGreaterThanOrEqual($timeSeconds * 1000, $this->defaultLogger->data->getTimestamp());
    }

    /**
     * Test if message will be logged to default logger when log level is lower than set min log level
     */
    public function testLoggingToDefaultLoggerWhenLogLevelIsLowerThanMinLogLevel()
    {
        Configuration::getInstance()->setMinLogLevel(Logger::INFO);
        Logger::logWarning('Some data');
        $this->assertTrue(
            $this->httpClient->calledAsync,
            'Default logger should send log when log level is lower than set min log level.'
        );
    }

    /**
     * Test if message will not be logged to default logger when log level is higher than set min log level
     */
    public function testNotLoggingToDefaultLoggerWhenLogLevelIsHigherThanMinLogLevel()
    {
        Configuration::getInstance()->setMinLogLevel(Logger::ERROR);
        Logger::logWarning('Some data');
        $this->assertFalse(
            $this->httpClient->calledAsync,
            'Default logger should not send log when log level is higher than set min log level.'
        );
    }
}