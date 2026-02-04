<?php

namespace BusinessLogic\Controllers;

use Logeecom\Infrastructure\AutoTest\AutoTestStatus;
use Logeecom\Infrastructure\Logger\LogData;
use Logeecom\Infrastructure\TaskExecution\AsyncProcessUrlProviderInterface;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerConfigInterface;
use Logeecom\Infrastructure\TaskExecution\TaskRunnerConfig;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\AutoTest\MockAutoTestService;
use Logeecom\Infrastructure\AutoTest\AutoTestService;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\QueueTaskStatusProvider;
use Logeecom\Infrastructure\TaskExecution\QueueService;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskExecutorInterface;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\TestRepositoryRegistry;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestAsyncProcessUrlProvider;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Controllers\AutoTestController;

class AutoTestControllerTest extends BaseTestWithServices
{
    public $service;
    public $controller;

    /**
     * @before
     * @inheritDoc
     */
    protected function before()
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

        $taskExecutor = ServiceRegister::getService(TaskExecutorInterface::CLASS_NAME);
        $queueService = ServiceRegister::getService(QueueService::CLASS_NAME);
        $taskRunnerConfig = ServiceRegister::getService(TaskRunnerConfigInterface::CLASS_NAME);

        $statusProvider = new QueueTaskStatusProvider($queueService);
        $this->service = new MockAutoTestService($taskExecutor, $statusProvider, $taskRunnerConfig);

        $me = $this;

        TestServiceRegister::registerService(
            AutoTestService::CLASS_NAME,
            function () use ($me) {
                return $me->service;
            }
        );

        TestRepositoryRegistry::registerRepository(LogData::getClassName(), MemoryRepository::getClassName());

        $this->controller = new AutoTestController();
    }

    public function testStartMethodCall()
    {
        // act
        $this->controller->start();

        // assert
        $this->assertEquals(array('startAutoTest'), $this->service->callHistory);
    }

    public function testStartSuccess()
    {
        // arrange
        $expected = array('success' => true, 'itemId' => $this->service->startAutoTestResult);

        // act
        $result = $this->controller->start();

        // assert
        $this->assertEquals($expected, $result);
    }

    public function testStartFailed()
    {
        // arrange
        $this->service->shouldFail = true;
        $expected = array('success' => false, 'error' => $this->service->failureMessage);

        // act
        $result = $this->controller->start();

        // assert
        $this->assertEquals($expected, $result);
    }

    public function testStop()
    {
        // act
        $this->controller->stop(function () {});

        // assert
        $this->assertEquals(array('stopAutoTestMode'), $this->service->callHistory);
    }

    public function testCheckStatusMethodCall()
    {
        // arrange
        $this->service->getAutoTestTaskStatusResult = new AutoTestStatus('test', true, 'Test', array());

        // act
        $this->controller->checkStatus(1);

        // assert
        $this->assertEquals(array('getAutoTestTaskStatus'), $this->service->callHistory);
    }

    public function testCheckStatusMethodResult()
    {
        // arrange
        $status = new AutoTestStatus('test', true, 'Test', array());
        $this->service->getAutoTestTaskStatusResult = $status;
        $expected = array(
            'finished' => $status->finished,
            'error' => $status->error,
            'logs' => $status->logs,
        );

        // act
        $result = $this->controller->checkStatus(1);

        // assert
        $this->assertEquals($expected, $result);
    }
}
