<?php

namespace Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution;

use Logeecom\Infrastructure\Serializer\Serializer;
use Logeecom\Infrastructure\TaskExecution\Task;

/**
 * Class FooTask
 *
 * @package Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution
 */
class FooTask extends Task
{
    private $dependency1;
    private $dependency2;
    private $methodsCallCount = array(
        'execute' => 0,
        'reconfigure' => 0,
    );

    public function __construct($dependency1 = '', $dependency2 = 0)
    {
        $this->dependency1 = $dependency1;
        $this->dependency2 = $dependency2;
    }

    /**
     * Transforms array into an serializable object,
     *
     * @param array $array Data that is used to instantiate serializable object.
     *
     * @return \Logeecom\Infrastructure\Serializer\Interfaces\Serializable
     *      Instance of serialized object.
     */
    public static function fromArray(array $array)
    {
        $entity = new static();

        $entity->dependency1 = $array['dependency_1'];
        $entity->dependency2 = $array['dependency_2'];
        $entity->methodsCallCount = $array['method_call_count'];

        return $entity;
    }

    /**
     * Transforms serializable object into an array.
     *
     * @return array Array representation of a serializable object.
     */
    public function toArray()
    {
        return array(
            'dependency_1' => $this->dependency1,
            'dependency_2' => $this->dependency2,
            'method_call_count' => $this->methodsCallCount,
        );
    }

    /**
     * String representation of object
     *
     * @return string the string representation of the object or null
     */
    public function serialize()
    {
        return Serializer::serialize(
            array(
                'dependency1' => $this->dependency1,
                'dependency2' => $this->dependency2,
                'methodsCallCount' => Serializer::serialize($this->methodsCallCount),
            )
        );
    }

    /**
     * Constructs the object
     *
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     *
     * @return void
     */
    public function unserialize($serialized)
    {
        $data = Serializer::unserialize($serialized);
        $this->dependency1 = $data['dependency1'];
        $this->dependency2 = $data['dependency2'];
        $this->methodsCallCount = Serializer::unserialize($data['methodsCallCount']);
    }

    public function execute()
    {
        $this->methodsCallCount['execute']++;
    }

    public function getMethodCallCount($methodName)
    {
        return !empty($this->methodsCallCount[$methodName]) ? $this->methodsCallCount[$methodName] : 0;
    }

    /**
     * @return string
     */
    public function getDependency1()
    {
        return $this->dependency1;
    }

    /**
     * @return int
     */
    public function getDependency2()
    {
        return $this->dependency2;
    }

    public function reconfigure()
    {
        $this->methodsCallCount['reconfigure']++;
    }

    public function canBeReconfigured()
    {
        return true;
    }
}