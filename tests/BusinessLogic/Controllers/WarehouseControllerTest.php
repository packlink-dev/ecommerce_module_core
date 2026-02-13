<?php

namespace Logeecom\Tests\BusinessLogic\Controllers;

use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Warehouse\MockWarehouseService;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\UpdateShippingServices\Interfaces\UpdateShippingServicesOrchestratorInterface;
use Packlink\BusinessLogic\Controllers\WarehouseController;
use Packlink\BusinessLogic\Warehouse\Warehouse;
use Packlink\BusinessLogic\Warehouse\WarehouseService;

class WarehouseControllerTest extends BaseTestWithServices
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

        $orchestrator = ServiceRegister::getService(UpdateShippingServicesOrchestratorInterface::class);
        $this->service = new MockWarehouseService($orchestrator);

        $me = $this;

        TestServiceRegister::registerService(
            WarehouseService::CLASS_NAME,
            function () use ($me) {
                return $me->service;
            }
        );

        $this->controller = new WarehouseController(TestServiceRegister::getService(WarehouseService::CLASS_NAME));;
    }

    public function testGetWarehouseMethodCalls()
    {
        // arrange
        $expected = array(array('getWarehouse' => array(true)));

        // act
        $this->controller->getWarehouse();

        // assert
        $this->assertEquals($expected, $this->service->callHistory);
    }

    public function testGetWarehouseResult()
    {
        // arrange
        $expected = new Warehouse();
        $expected->email = 'test';
        $this->service->getWarehouseResult = $expected;

        // act
        $result = $this->controller->getWarehouse();

        // assert
        $this->assertEquals($expected, $result);
    }

    public function testUpdateWarehouseMethodCalls()
    {
        // arrange
        $payload = array('t1', 't2', 't3');
        $expected = array(array('updateWarehouseData' => array($payload)));

        // act
        $this->controller->updateWarehouse($payload);

        // assert
        $this->assertEquals($expected, $this->service->callHistory);
    }

    public function testUpdateWarehouseResult()
    {
        // arrange
        $expected = new Warehouse();
        $expected->email = 'test';
        $this->service->updateWarehouseDataResult = $expected;

        // act
        $result = $this->controller->updateWarehouse(array());

        // assert
        $this->assertEquals($expected, $result);
    }

    /**
     * @after
     * @inheritDoc
     */
    protected function after()
    {
        parent::after();
    }
}
