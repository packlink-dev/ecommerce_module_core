<?php

namespace Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution;

use Logeecom\Infrastructure\TaskExecution\Task;

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
     * String representation of object
     *
     * @return string the string representation of the object or null
     */
    public function serialize()
    {
        return serialize(
            array(
                'dependency1' => $this->dependency1,
                'dependency2' => $this->dependency2,
                'methodsCallCount' => serialize($this->methodsCallCount),
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
        $data = unserialize($serialized);
        $this->dependency1 = $data['dependency1'];
        $this->dependency2 = $data['dependency2'];
        $this->methodsCallCount = unserialize($data['methodsCallCount']);
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