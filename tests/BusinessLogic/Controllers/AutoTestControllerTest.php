<?php

namespace BusinessLogic\Controllers;

use Logeecom\Infrastructure\AutoTest\AutoTestStatus;
use Logeecom\Infrastructure\Logger\LogData;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\AutoTest\MockAutoTestService;
use Logeecom\Infrastructure\AutoTest\AutoTestService;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\TestRepositoryRegistry;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Controllers\AutoTestController;

class AutoTestControllerTest extends BaseTestWithServices
{
    public $service;
    public $controller;

    protected function setUp()
    {
        parent::setUp();
        $this->service = new MockAutoTestService();

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