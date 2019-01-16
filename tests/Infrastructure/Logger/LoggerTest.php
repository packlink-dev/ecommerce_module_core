<?php

namespace Logeecom\Tests\Infrastructure\logger;

use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\Logger\LoggerConfiguration;
use Logeecom\Tests\Common\BaseTestWithServices;
use Logeecom\Tests\Common\TestComponents\TestHttpClient;
use Logeecom\Tests\Common\TestServiceRegister;

class LoggerTest extends BaseTestWithServices
{
    /**
     * @var TestHttpClient
     */
    public $httpClient;

    public function setUp()
    {
        parent::setUp();

        $this->shopConfig->setIntegrationName('Shop1');
        $this->shopConfig->setDefaultLoggerEnabled(true);

        $me = $this;
        $this->httpClient = new TestHttpClient();
        TestServiceRegister::registerService(
            HttpClient::CLASS_NAME,
            function () use ($me) {
                return $me->httpClient;
            }
        );
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
        LoggerConfiguration::setDefaultLoggerEnabled(false);
        Logger::logInfo('Some data');
        $this->assertFalse($this->httpClient->calledAsync, 'Default logger should not send log when it is off.');
    }

    /**
     * Test if message will be logged to default logger when it is on
     */
    public function testLoggingToDefaultLoggerWhenItIsOn()
    {
        LoggerConfiguration::setDefaultLoggerEnabled(true);
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
        LoggerConfiguration::getInstance()->setMinLogLevel(Logger::INFO);
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
        LoggerConfiguration::getInstance()->setMinLogLevel(Logger::ERROR);
        Logger::logWarning('Some data');
        $this->assertFalse(
            $this->httpClient->calledAsync,
            'Default logger should not send log when log level is higher than set min log level.'
        );
    }
}