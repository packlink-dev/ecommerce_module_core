<?php

namespace Logeecom\Infrastructure\AutoTest;

use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\Serializer\Serializer;
use Logeecom\Infrastructure\TaskExecution\Task;

/**
 * Class AutoTestTask.
 *
 * @package Logeecom\Infrastructure\AutoTest
 */
class AutoTestTask extends Task
{
    /**
     * Dummy data for the task.
     *
     * @var string
     */
    protected $data;

    /**
     * AutoTestTask constructor.
     *
     * @param string $data Dummy data.
     */
    public function __construct($data)
    {
        $this->data = $data;
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
        return new static($array['data']);
    }

    /**
     * Transforms serializable object into an array.
     *
     * @return array Array representation of a serializable object.
     */
    public function toArray()
    {
        return array('data' => $this->data);
    }

    /**
     * String representation of object.
     *
     * @return string The string representation of the object or null.
     */
    public function serialize()
    {
        return Serializer::serialize(array($this->data));
    }

    /**
     * Constructs the object.
     *
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     */
    public function unserialize($serialized)
    {
        list($this->data) = Serializer::unserialize($serialized);
    }

    /**
     * Runs task logic.
     */
    public function execute()
    {
        $this->reportProgress(5);
        Logger::logInfo('Auto-test task started');

        $this->reportProgress(50);
        Logger::logInfo('Auto-test task parameters', 'Core', array($this->data));

        $this->reportProgress(100);
        Logger::logInfo('Auto-test task ended');
    }
}
