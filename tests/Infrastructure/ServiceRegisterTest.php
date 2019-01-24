<?php

namespace Logeecom\Tests\Infrastructure;

use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestService;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use PHPUnit\Framework\TestCase;

class ServiceRegisterTest extends TestCase
{
    /**
     * Test simple registering the service and getting the instance back
     *
     * @throws \InvalidArgumentException
     */
    public function testGetInstance()
    {
        $service = ServiceRegister::getInstance();

        $this->assertInstanceOf(
            '\Logeecom\Infrastructure\ServiceRegister',
            $service,
            'Failed to retrieve registered instance of interface.'
        );
    }

    /**
     * Test simple registering the service and getting the instance back
     *
     */
    public function testSimpleRegisterAndGet()
    {
        new TestServiceRegister(
            array(
                TestService::CLASS_NAME => function () {
                    return new TestService('first');
                },
            )
        );

        $result = ServiceRegister::getService(TestService::CLASS_NAME);

        $this->assertInstanceOf(
            TestService::CLASS_NAME,
            $result,
            'Failed to retrieve registered instance of interface.'
        );
    }

    /**
     * Test simple registering the service via static call and getting the instance back
     */
    public function testStaticSimpleRegisterAndGet()
    {
        ServiceRegister::registerService(
            'test 2',
            function () {
                return new TestService('first');
            }
        );

        $result = ServiceRegister::getService(TestService::CLASS_NAME);

        $this->assertInstanceOf(
            TestService::CLASS_NAME,
            $result,
            'Failed to retrieve registered instance of interface.'
        );
    }

    /**
     * Test throwing exception when service is not registered.
     * @expectedException \Logeecom\Infrastructure\Exceptions\ServiceNotRegisteredException
     */
    public function testGettingServiceWhenItIsNotRegistered()
    {
        ServiceRegister::getService('SomeService');
    }

    /**
     * Test throwing exception when trying to register service with non callable delegate
     *
     * @expectedException \InvalidArgumentException
     */
    public function testRegisteringServiceWhenDelegateIsNotCallable()
    {
        new TestServiceRegister(
            array(
                TestService::CLASS_NAME => 'Some non callable string',
            )
        );
    }
}
