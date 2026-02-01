<?php

namespace Logeecom\Tests\BusinessLogic\Tasks;

use Logeecom\Infrastructure\Serializer\Concrete\JsonSerializer;
use Logeecom\Infrastructure\Serializer\Concrete\NativeSerializer;
use Logeecom\Infrastructure\Serializer\Serializer;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Logeecom\Infrastructure\TaskExecution\BatchTaskCleanupTask;

/**
 * Class BatchTaskCleanupTaskSerializationTest
 *
 * @package BusinessLogic\Tasks
 */
class BatchTaskCleanupTaskSerializationTest extends BaseTestWithServices
{
    public function testNativeSerialize()
    {
        // arrange
        TestServiceRegister::registerService(
            Serializer::CLASS_NAME,
            function () {
                return new NativeSerializer();
            }
        );

        $task = new BatchTaskCleanupTask(array('t1', 't2'), array('t3'));
        $serialized = Serializer::serialize($task);

        // act
        $unserialized = Serializer::unserialize($serialized);

        // assert
        $this->assertEquals($task, $unserialized);
    }

    public function testNativeSerializeNoTaskTypes()
    {
        // arrange
        TestServiceRegister::registerService(
            Serializer::CLASS_NAME,
            function () {
                return new NativeSerializer();
            }
        );

        $task = new BatchTaskCleanupTask(array('t1', 't2'));
        $serialized = Serializer::serialize($task);

        // act
        $unserialized = Serializer::unserialize($serialized);

        // assert
        $this->assertEquals($task, $unserialized);
    }

    public function testJsonSerialize()
    {
        // arrange
        TestServiceRegister::registerService(
            Serializer::CLASS_NAME,
            function () {
                return new JsonSerializer();
            }
        );

        $task = new BatchTaskCleanupTask(array('t1', 't2'), array('t3'));
        $serialized = Serializer::serialize($task);

        // act
        $unserialized = Serializer::unserialize($serialized);

        // assert
        $this->assertEquals($task, $unserialized);
    }

    public function testJsonSerializeNoTaskTypes()
    {
        // arrange
        TestServiceRegister::registerService(
            Serializer::CLASS_NAME,
            function () {
                return new JsonSerializer();
            }
        );

        $task = new BatchTaskCleanupTask(array('t1', 't2'));
        $serialized = Serializer::serialize($task);

        // act
        $unserialized = Serializer::unserialize($serialized);

        // assert
        $this->assertEquals($task, $unserialized);
    }
}
