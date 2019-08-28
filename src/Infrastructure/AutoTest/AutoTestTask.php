<?php

namespace Logeecom\Infrastructure\AutoTest;

use Logeecom\Infrastructure\Logger\Logger;
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
     * String representation of object.
     *
     * @return string The string representation of the object or null.
     */
    public function serialize()
    {
        return serialize(array('data' => $this->data));
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
        $data = unserialize($serialized);
        $this->data = $data['data'];
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
