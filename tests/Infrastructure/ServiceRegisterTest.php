<?php

namespace Logeecom\Tests\Infrastructure;

use Logeecom\Tests\Common\TestServiceRegister;
use PHPUnit\Framework\TestCase;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Tests\Common\TestComponents\TestService;

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
     *
     * @throws \Logeecom\Infrastructure\Exceptions\ServiceAlreadyRegisteredException
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
     * Test throwing exception when service is not registered
     *
     */
    public function testGettingServiceWhenItIsNotRegistered()
    {
        $this->expectException('\Logeecom\Infrastructure\Exceptions\ServiceNotRegisteredException');
        ServiceRegister::getService('SomeService');
    }

    /**
     * Test registering service that is already registered
     *
     * @throws \Logeecom\Infrastructure\Exceptions\ServiceAlreadyRegisteredException
     */
    public function testRegisteringServiceThatIsAlreadyRegistered()
    {
        new TestServiceRegister(
            array(
                TestService::CLASS_NAME => function () {
                    return new TestService('first');
                },
            )
        );

        $this->expectException('\Logeecom\Infrastructure\Exceptions\ServiceAlreadyRegisteredException');
        ServiceRegister::registerService(
            TestService::CLASS_NAME,
            function () {
                return new TestService('second');
            }
        );
    }

    /**
     * Test throwing exception when trying to register service with non callable delegate
     *
     * @throws \InvalidArgumentException
     */
    public function testRegisteringServiceWhenDelegateIsNotCallable()
    {
        $this->expectException('\InvalidArgumentException');
        new TestServiceRegister(
            array(
                TestService::CLASS_NAME => 'Some non callable string',
            )
        );
    }
}
