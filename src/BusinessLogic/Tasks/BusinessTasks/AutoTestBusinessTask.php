<?php

namespace Packlink\BusinessLogic\Tasks\BusinessTasks;

use Logeecom\Infrastructure\Logger\Logger;
use Packlink\BusinessLogic\Tasks\Interfaces\BusinessTask;

class AutoTestBusinessTask implements BusinessTask
{
    /**
     * Dummy data for the task.
     *
     * @var string
     */
    protected $data;

    /**
     * AutoTestBusinessTask constructor.
     *
     * @param string $data Dummy data.
     */
    public function __construct(string $data)
    {
        $this->data = $data;
    }

    /**
     * Runs task logic.
     *
     * @return \Generator
     */
    public function execute(): \Generator
    {
        yield 5;
        Logger::logInfo('Auto-test task started');

        yield 50;
        Logger::logInfo('Auto-test task parameters', 'Core', array($this->data));

        yield 100;
        Logger::logInfo('Auto-test task ended');
    }

    /**
     * Serialize task to array.
     *
     * @return array Task data.
     */
    public function toArray(): array
    {
        return array('data' => $this->data);
    }

    /**
     * Deserialize task from array.
     *
     * @param array $data Task data.
     *
     * @return static Task instance.
     */
    public static function fromArray(array $data): BusinessTask
    {
        return new static($data['data']);
    }
}
