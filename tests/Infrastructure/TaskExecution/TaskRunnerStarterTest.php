<?php

namespace Logeecom\Tests\Infrastructure\TaskExecution;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\Serializer\Serializer;
use Logeecom\Infrastructure\TaskExecution\AsyncProcessStarterService;
use Logeecom\Infrastructure\TaskExecution\AsyncProcessUrlProviderInterface;
use Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException;
use Logeecom\Infrastructure\TaskExecution\Interfaces\AsyncProcessService;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerConfigInterface;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerStatusStorage;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup;
use Logeecom\Infrastructure\TaskExecution\Process;
use Logeecom\Infrastructure\TaskExecution\TaskRunner;
use Logeecom\Infrastructure\TaskExecution\TaskRunnerConfig;
use Logeecom\Infrastructure\TaskExecution\TaskRunnerStarter;
use Logeecom\Infrastructure\TaskExecution\TaskRunnerStatus;
use Logeecom\Infrastructure\Utility\GuidProvider;
use Logeecom\Tests\Infrastructure\Common\BaseInfrastructureTestWithServices;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryStorage;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestAsyncProcessUrlProvider;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestRunnerStatusStorage;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestTaskRunner;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestTaskRunnerWakeupService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestHttpClient;
use Logeecom\Tests\Infrastructure\Common\TestComponents\Utility\TestGuidProvider;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;

/**
 * Class TaskRunnerStarterTest
 *
 * @package Logeecom\Tests\Infrastructure\TaskExecution
 */
class TaskRunnerStarterTest extends BaseInfrastructureTestWithServices
{
    /** @var AsyncProcessService */
    private $asyncProcessStarter;
    /** @var \Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestTaskRunner */
    private $taskRunner;
    /** @var TestRunnerStatusStorage */
    private $runnerStatusStorage;
    /** @var TestGuidProvider */
    private $guidProvider;
    /** @var TaskRunnerStarter */
    private $runnerStarter;
    /** @var string */
    private $guid;

    public function testTaskRunnerIsStartedWithProperGuid()
    {
        // Act
        $this->runnerStarter->run();

        // Assert
        $runCallHistory = $this->taskRunner->getMethodCallHistory('run');
        $setGuidCallHistory = $this->taskRunner->getMethodCallHistory('setGuid');
        $this->assertCount(1, $runCallHistory, 'Run call must start runner.');
        $this->assertCount(1, $setGuidCallHistory, 'Run call must set runner guid.');
        $this->assertEquals($this->guid, $setGuidCallHistory[0]['guid'], 'Run call must set runner guid.');
    }

    /**
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusChangeException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException
     * @throws \Exception
     */
    public function testRunningTaskRunnerWhenExpired()
    {
        // Arrange
        $currentTimestamp = $this->timeProvider->getCurrentLocalTime()->getTimestamp();
        $expiredTimestamp = $currentTimestamp - TaskRunnerStatus::MAX_ALIVE_TIME - 1;
        $this->runnerStatusStorage->setStatus(new TaskRunnerStatus($this->guid, $expiredTimestamp));

        // Act
        $this->runnerStarter->run();

        // Assert
        $runCallHistory = $this->taskRunner->getMethodCallHistory('run');
        $this->assertCount(0, $runCallHistory, 'Run call must fail when runner is expired.');
        $this->assertStringStartsWith(
            'Failed to run task runner',
            $this->shopLogger->data->getMessage(),
            'Run call must throw TaskRunnerRunException when runner is expired'
        );
        $this->assertStringEndsWith(
            'Runner is expired.',
            $this->shopLogger->data->getMessage(),
            'Debug message must be logged when trying to run expired task runner.'
        );
    }

    /**
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusChangeException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException
     * @throws \Exception
     */
    public function testRunningTaskRunnerWithActiveGuidDoNotMatchGuidGeneratedWithWakeup()
    {
        // Arrange
        $currentTimestamp = $this->timeProvider->getCurrentLocalTime()->getTimestamp();
        $this->runnerStatusStorage->setStatus(new TaskRunnerStatus('different_active_guid', $currentTimestamp));

        // Act
        $this->runnerStarter->run();

        // Assert
        $runCallHistory = $this->taskRunner->getMethodCallHistory('run');
        $this->assertCount(0, $runCallHistory, 'Run call must fail when runner guid is not set as active runner guid.');
        $this->assertStringStartsWith(
            'Failed to run task runner.',
            $this->shopLogger->data->getMessage(),
            'Run call must throw TaskRunnerRunException when runner guid is not set as active runner guid.'
        );
        $this->assertStringEndsWith(
            'Runner guid is not set as active.',
            $this->shopLogger->data->getMessage(),
            'Debug message must be logged when trying to run task runner with guid that is not set as active runner guid.'
        );
    }

    public function testRunWhenRunnerStatusServiceIsUnavailable()
    {
        $this->runnerStatusStorage->setExceptionResponse(
            'getStatus',
            new TaskRunnerStatusStorageUnavailableException('Simulation for unavailable storage exception.')
        );

        // Act
        $this->runnerStarter->run();

        $this->assertStringStartsWith(
            'Failed to run task runner.',
            $this->shopLogger->data->getMessage(),
            'Run call must throw TaskRunnerRunException when runner status storage is unavailable.'
        );
        $this->assertStringEndsWith('Runner status storage unavailable.', $this->shopLogger->data->getMessage());
    }

    public function testRunInCaseOfUnexpectedException()
    {
        $this->runnerStatusStorage->setExceptionResponse(
            'getStatus',
            new \Exception('Simulation for unexpected exception.')
        );

        // Act
        $this->runnerStarter->run();
        $this->assertStringStartsWith(
            'Failed to run task runner.',
            $this->shopLogger->data->getMessage(),
            'Run call must throw TaskRunnerRunException when unexpected exception occurs.'
        );
        $this->assertStringEndsWith('Unexpected error occurred.', $this->shopLogger->data->getMessage());
    }

    public function testTaskStarterMustBeRunnableAfterDeserialization()
    {
        // Arrange
        /** @var TaskRunnerStarter $unserializedRunnerStarter */
        $unserializedRunnerStarter = Serializer::unserialize(Serializer::serialize($this->runnerStarter));

        // Act
        $unserializedRunnerStarter->run();

        // Assert
        $runCallHistory = $this->taskRunner->getMethodCallHistory('run');
        $setGuidCallHistory = $this->taskRunner->getMethodCallHistory('setGuid');
        $this->assertCount(1, $runCallHistory, 'Run call must start runner.');
        $this->assertCount(1, $setGuidCallHistory, 'Run call must set runner guid.');
        $this->assertEquals($this->guid, $setGuidCallHistory[0]['guid'], 'Run call must set runner guid.');
    }

    /**
     * @before
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusChangeException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException
     * @throws \Exception
     */
    protected function before()
    {
        parent::before();

        RepositoryRegistry::registerRepository(Process::CLASS_NAME, MemoryRepository::getClassName());

        TestServiceRegister::registerService(
            AsyncProcessUrlProviderInterface::CLASS_NAME,
            function () {
                return new TestAsyncProcessUrlProvider();
            }
        );

        TestServiceRegister::registerService(
            TaskRunnerConfigInterface::CLASS_NAME,
            function () {
                $config = TestServiceRegister::getService(Configuration::CLASS_NAME);
                $urlProvider = TestServiceRegister::getService(AsyncProcessUrlProviderInterface::CLASS_NAME);

                return new TaskRunnerConfig($config, $urlProvider);
            }
        );

        TestServiceRegister::registerService(
            AsyncProcessUrlProviderInterface::CLASS_NAME,
            function () {
                return new TestAsyncProcessUrlProvider();
            }
        );

        TestServiceRegister::registerService(
            TaskRunnerConfigInterface::CLASS_NAME,
            function () {
                $config = TestServiceRegister::getService(Configuration::CLASS_NAME);
                $urlProvider = TestServiceRegister::getService(AsyncProcessUrlProviderInterface::CLASS_NAME);

                return new TaskRunnerConfig($config, $urlProvider);
            }
        );

        $runnerStatusStorage = new TestRunnerStatusStorage();
        $taskRunnerConfig = TestServiceRegister::getService(TaskRunnerConfigInterface::CLASS_NAME);
        $taskRunner = new TestTaskRunner($taskRunnerConfig);
        $guidProvider = TestGuidProvider::getInstance();

        TestServiceRegister::registerService(
            AsyncProcessService::CLASS_NAME,
            function () {
                return AsyncProcessStarterService::getInstance();
            }
        );
        TestServiceRegister::registerService(
            TaskRunnerStatusStorage::CLASS_NAME,
            function () use ($runnerStatusStorage) {
                return $runnerStatusStorage;
            }
        );
        TestServiceRegister::registerService(
            TaskRunner::CLASS_NAME,
            function () use ($taskRunner) {
                return $taskRunner;
            }
        );
        TestServiceRegister::registerService(
            GuidProvider::CLASS_NAME,
            function () use ($guidProvider) {
                return $guidProvider;
            }
        );
        TestServiceRegister::registerService(
            HttpClient::CLASS_NAME,
            function () {
                return new TestHttpClient();
            }
        );
        TestServiceRegister::registerService(
            TaskRunnerWakeup::CLASS_NAME,
            function () {
                return new TestTaskRunnerWakeupService();
            }
        );

        Logger::resetInstance();

        $this->asyncProcessStarter = AsyncProcessStarterService::getInstance();
        $this->runnerStatusStorage = $runnerStatusStorage;
        $this->taskRunner = $taskRunner;
        $this->guidProvider = $guidProvider;

        $currentTimestamp = $this->timeProvider->getCurrentLocalTime()->getTimestamp();
        $this->guid = 'test_runner_guid';
        $this->runnerStarter = new TaskRunnerStarter($this->guid);
        $this->runnerStatusStorage->setStatus(new TaskRunnerStatus($this->guid, $currentTimestamp));
    }

    /**
     * @after
     * @inheritdoc
     */
    protected function after()
    {
        AsyncProcessStarterService::resetInstance();
        MemoryStorage::reset();
        parent::after();
    }
}
