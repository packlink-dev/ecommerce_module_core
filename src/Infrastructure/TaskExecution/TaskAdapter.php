<?php

namespace Logeecom\Infrastructure\TaskExecution;

use Generator;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\Serializer\Serializer;
use Packlink\BusinessLogic\Tasks\Interfaces\BusinessTask;

class TaskAdapter extends Task
{
    /**
     * Business task instance (e.g., SendDraftBusinessTask).
     *
     * @var BusinessTask
     */
    private $businessTask;

    /**
     * Business task class name.
     *
     * @var string
     */
    private $businessTaskClass;

    /**
     * TaskAdapter constructor.
     *
     * @param BusinessTask $businessTask Business task instance.
     */
    public function __construct(BusinessTask $businessTask)
    {
        $this->businessTask = $businessTask;
        $this->businessTaskClass = get_class($businessTask);
    }

    /**
     * Execute a wrapped business task.
     *
     * Delegates execution to the business task's execute() method.
     * If task returns Generator (uses yield), handles progress tracking:
     * - yield; (no value) → reportAlive()
     * - yield 50; → reportProgress(50)
     * - yield 'message'; → log message
     *
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\AbortTaskExecutionException
     * @throws \Exception
     */
    public function execute()
    {
        $result = $this->businessTask->execute();

        if ($result instanceof Generator) {
            $this->executeWithProgressTracking($result);
        }
    }

    /**
     * Execute generator with progress tracking.
     *
     * Iterates over generator and handles yielded values:
     * - null (yield;) → reportAlive()
     * - int/float (yield 50;) → reportProgress(50)
     * - string (yield 'message';) → log message
     *
     * @param Generator $generator Generator returned by business task.
     *
     * @return void
     */

    private function executeWithProgressTracking(Generator $generator)
    {
        foreach ($generator as $value) {
            if ($value === null) {
                $this->reportAlive();
                continue;
            }

            if (is_int($value) || is_float($value)) {
                $this->reportProgress($value);
                continue;
            }

            if (is_string($value)) {
                Logger::logInfo($value, 'Task', array('task' => $this->businessTaskClass));
            }
        }
    }

    /**
     * Serialize business task for storage in QueueItem.
     *
     * @return string Serialized task data.
     */
    public function serialize()
    {
        return Serializer::serialize(array(
            'class' => $this->businessTaskClass,
            'data' => $this->businessTask->toArray(),
        ));
    }

    /**
     * Deserialize business task from storage.
     *
     * @param string $serialized Serialized task data.
     *
     * @return void
     */
    public function unserialize($serialized)
    {
        $data = Serializer::unserialize($serialized);
        $this->businessTaskClass = $data['class'];

        // Reconstruct business task using fromArray()
        $this->businessTask = call_user_func(array($this->businessTaskClass, 'fromArray'), $data['data']);
    }

    /**
     * Get task type (for QueueItem).
     *
     * @return string Task class name.
     */
    public function getType()
    {
        return $this->businessTaskClass ?: parent::getType();
    }

    public static function fromArray(array $array)
    {
        if (empty($array['class']) || !is_string($array['class'])) {
            throw new \InvalidArgumentException('TaskAdapter::fromArray expects "class" key.');
        }

        $class = $array['class'];

        if (!class_exists($class)) {
            throw new \InvalidArgumentException('BusinessTask class does not exist: ' . $class);
        }

        if (!is_callable(array($class, 'fromArray'))) {
            throw new \InvalidArgumentException('BusinessTask class must implement static fromArray(): ' . $class);
        }

        $data = isset($array['data']) && is_array($array['data']) ? $array['data'] : array();

        /** @var BusinessTask $businessTask */
        $businessTask = call_user_func(array($class, 'fromArray'), $data);

        return new static($businessTask);
    }

    public function toArray()
    {
        return array(
            'class' => $this->businessTaskClass,
            'data' => $this->businessTask ? $this->businessTask->toArray() : array(),
        );
    }

    public function __serialize()
    {
        return $this->toArray();
    }

    public function __unserialize($data)
    {
        if (!is_array($data)) {
            throw new \InvalidArgumentException('TaskAdapter::__unserialize expects array.');
        }

        // Reuse fromArray logic, but populate current instance (since __unserialize cannot return new one)
        if (empty($data['class']) || !is_string($data['class'])) {
            throw new \InvalidArgumentException('TaskAdapter::__unserialize expects "class" key.');
        }

        $this->businessTaskClass = $data['class'];

        if (!class_exists($this->businessTaskClass)) {
            throw new \InvalidArgumentException('BusinessTask class does not exist: ' . $this->businessTaskClass);
        }

        if (!is_callable(array($this->businessTaskClass, 'fromArray'))) {
            throw new \InvalidArgumentException(
                'BusinessTask class must implement static fromArray(): ' . $this->businessTaskClass
            );
        }

        $taskData = isset($data['data']) && is_array($data['data']) ? $data['data'] : array();

        $this->businessTask = call_user_func(array($this->businessTaskClass, 'fromArray'), $taskData);
    }
}
