<?php

namespace Logeecom\Tests\Infrastructure\TaskExecution;

use Logeecom\Tests\Common\TestComponents\Logger\TestDefaultLogger;
use Logeecom\Tests\Common\TestServiceRegister;
use PHPUnit\Framework\TestCase;
use Logeecom\Infrastructure\Interfaces\DefaultLoggerAdapter;
use Logeecom\Infrastructure\Interfaces\Exposed\TaskRunnerStatusStorage;
use Logeecom\Infrastructure\Interfaces\Required\AsyncProcessStarter;
use Logeecom\Infrastructure\Configuration;
use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\Interfaces\Required\ShopLoggerAdapter;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusChangeException;
use Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException;
use Logeecom\Infrastructure\TaskExecution\TaskRunnerStarter;
use Logeecom\Infrastructure\TaskExecution\TaskRunnerStatus;
use Logeecom\Infrastructure\TaskExecution\TaskRunnerWakeup;
use Logeecom\Infrastructure\Utility\GuidProvider;
use Logeecom\Infrastructure\Utility\TimeProvider;
use Logeecom\Tests\Common\TestComponents\TestShopConfiguration;
use Logeecom\Tests\Common\TestComponents\Logger\TestShopLogger;
use Logeecom\Tests\Common\TestComponents\TaskExecution\TestAsyncProcessStarter;
use Logeecom\Tests\Common\TestComponents\TaskExecution\TestRunnerStatusStorage;
use Logeecom\Tests\Common\TestComponents\TestHttpClient;
use Logeecom\Tests\Common\TestComponents\Utility\TestGuidProvider;
use Logeecom\Tests\Common\TestComponents\Utility\TestTimeProvider;

class TaskRunnerWakeupTest extends TestCase
{
    /** @var TestAsyncProcessStarter */
    private $asyncProcessStarter;
    /** @var TestRunnerStatusStorage */
    private $runnerStatusStorage;
    /** @var TestTimeProvider */
    private $timeProvider;
    /** @var TestGuidProvider */
    private $guidProvider;
    /** @var TaskRunnerWakeup */
    private $runnerWakeup;
    /** @var TestShopLogger */
    private $logger;

    /**
     * @throws \Exception
     */
    protected function setUp()
    {
        $asyncProcessStarter = new TestAsyncProcessStarter();
        $runnerStatusStorage = new TestRunnerStatusStorage();
        $timeProvider = new TestTimeProvider();
        $guidProvider = TestGuidProvider::getInstance();

        $shopLogger = new TestShopLogger();

        new TestServiceRegister(
            array(
                AsyncProcessStarter::CLASS_NAME => function () use ($asyncProcessStarter) {
                    return $asyncProcessStarter;
                },
                TaskRunnerStatusStorage::CLASS_NAME => function () use ($runnerStatusStorage) {
                    return $runnerStatusStorage;
                },
                TimeProvider::CLASS_NAME => function () use ($timeProvider) {
                    return $timeProvider;
                },
                GuidProvider::CLASS_NAME => function () use ($guidProvider) {
                    return $guidProvider;
                },
                DefaultLoggerAdapter::CLASS_NAME => function () {
                    return new TestDefaultLogger();
                },
                ShopLoggerAdapter::CLASS_NAME => function () use ($shopLogger) {
                    return $shopLogger;
                },
                Configuration::CLASS_NAME => function () {
                    return new TestShopConfiguration();
                },
                HttpClient::CLASS_NAME => function () {
                    return new TestHttpClient();
                },
            )
        );

        new Logger();

        $this->asyncProcessStarter = $asyncProcessStarter;
        $this->runnerStatusStorage = $runnerStatusStorage;
        $this->timeProvider = $timeProvider;
        $this->guidProvider = $guidProvider;
        $this->runnerWakeup = new TaskRunnerWakeup();
        $this->logger = $shopLogger;
    }

    /**
     *
     * @throws \Exception
     */
    public function testWakeupWhenThereIsNoLiveRunner()
    {
        // Arrange
        $guid = 'test_runner_guid';
        $this->guidProvider->setGuid($guid);

        // Act
        $this->runnerWakeup->wakeup();

        // Assert
        $startCallHistory = $this->asyncProcessStarter->getMethodCallHistory('start');
        $this->assertCount(
            1,
            $startCallHistory,
            'Wakeup call when there is no live runner must start runner asynchronously.'
        );

        /** @var TaskRunnerStarter $runnerStarter */
        $runnerStarter = $startCallHistory[0]['runner'];
        $this->assertInstanceOf(
            '\Logeecom\Infrastructure\TaskExecution\TaskRunnerStarter',
            $runnerStarter,
            'Wakeup call when there is no live runner must start runner asynchronously using TaskRunnerStarter as runner starter component.'
        );
        $this->assertSame($guid, $runnerStarter->getGuid(), 'Wakeup call must generate guid for new runner starter.');

        $setStatusCallHistory = $this->runnerStatusStorage->getMethodCallHistory('setStatus');
        $this->assertCount(
            1,
            $setStatusCallHistory,
            'Wakeup call when there is no live runner must set new status before starting runner again.'
        );

        /** @var TaskRunnerStatus $runnerStatus */
        $runnerStatus = $setStatusCallHistory[0]['status'];
        $this->assertEquals($guid, $runnerStatus->getGuid(), 'Wakeup call must generate guid for new runner status.');
        $this->assertSame(
            $this->timeProvider->getCurrentLocalTime()->getTimestamp(),
            $runnerStatus->getAliveSinceTimestamp(),
            'Wakeup call must active since timestamp to current timestamp for new runner instance.'
        );
    }

    /**
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusChangeException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException
     * @throws \Exception
     */
    public function testWakeupWhenRunnerIsAlreadyLive()
    {
        $currentTimestamp = $this->timeProvider->getCurrentLocalTime()->getTimestamp();
        $this->runnerStatusStorage->setStatus(new TaskRunnerStatus('test', $currentTimestamp));

        $this->runnerWakeup->wakeup();

        $startCallHistory = $this->asyncProcessStarter->getMethodCallHistory('start');
        $this->assertCount(
            0,
            $startCallHistory,
            'Wakeup call when there is already live runner must not start runner again.'
        );
    }

    /**
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusChangeException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException
     * @throws \Exception
     */
    public function testWakeupWhenRunnerIsExpired()
    {
        $currentTimestamp = $this->timeProvider->getCurrentLocalTime()->getTimestamp();
        $expiredAliveSinceTimestamp = $currentTimestamp - TaskRunnerStatus::MAX_ALIVE_TIME - 1;
        $this->runnerStatusStorage->setStatus(new TaskRunnerStatus('test', $expiredAliveSinceTimestamp));

        $this->runnerWakeup->wakeup();

        $startCallHistory = $this->asyncProcessStarter->getMethodCallHistory('start');
        $this->assertCount(
            1,
            $startCallHistory,
            'Wakeup call when there is expired runner must start runner asynchronously.'
        );

        /** @var TaskRunnerStarter $runnerStarter */
        $runnerStarter = $startCallHistory[0]['runner'];
        $this->assertInstanceOf(
            '\Logeecom\Infrastructure\TaskExecution\TaskRunnerStarter',
            $runnerStarter,
            'Wakeup call when there is expired runner must start runner asynchronously using TaskRunnerStarter as runner starter component'
        );
    }

    public function testWakeupWhenRunnerStatusServiceFailToSaveNewStatus()
    {
        // Arrange
        $this->runnerStatusStorage->setExceptionResponse(
            'setStatus',
            new TaskRunnerStatusChangeException('Disallow status change')
        );

        // Act
        $this->runnerWakeup->wakeup();

        // Assert
        $startCallHistory = $this->asyncProcessStarter->getMethodCallHistory('start');
        $this->assertCount(
            0,
            $startCallHistory,
            'Wakeup call when new status setting fails must not start new runner instance.'
        );
        $this->assertContains(
            'Runner status storage failed to set new active state.',
            $this->logger->data->getMessage()
        );
    }

    public function testWakeupWhenRunnerStatusServiceIsUnavailable()
    {
        // Arrange
        $this->runnerStatusStorage->setExceptionResponse(
            'getStatus',
            new TaskRunnerStatusStorageUnavailableException('Simulation for unavailable storage exception.')
        );

        // Act
        $this->runnerWakeup->wakeup();

        // Assert
        $startCallHistory = $this->asyncProcessStarter->getMethodCallHistory('start');
        $this->assertCount(
            0,
            $startCallHistory,
            'Wakeup call when tasks status storage is unavailable must not start new runner instance.'
        );
        $this->assertContains('Runner status storage unavailable.', $this->logger->data->getMessage());
    }

    public function testWakeupInCaseOfUnexpectedException()
    {
        // Arrange
        $this->runnerStatusStorage->setExceptionResponse(
            'getStatus',
            new \Exception('Simulation for unexpected exception.')
        );

        // Act
        $this->runnerWakeup->wakeup();

        // Assert
        $startCallHistory = $this->asyncProcessStarter->getMethodCallHistory('start');
        $this->assertCount(
            0,
            $startCallHistory,
            'Wakeup call when exception occurs must not start new runner instance.'
        );
        $this->assertContains('Unexpected error occurred.', $this->logger->data->getMessage());
    }
}
