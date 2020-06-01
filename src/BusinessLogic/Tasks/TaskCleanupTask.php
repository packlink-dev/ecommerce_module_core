<?php

namespace Packlink\BusinessLogic\Tasks;

use Logeecom\Infrastructure\ORM\QueryFilter\Operators;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\Serializer\Serializer;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Interfaces\Priority;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Infrastructure\TaskExecution\Task;
use Logeecom\Infrastructure\Utility\TimeProvider;

/**
 * Class TaskCleanupTask.
 * In charge for deleting from the database specific tasks in specific statuses older than specific age (in seconds).
 *
 * @package Packlink\BusinessLogic\Tasks
 */
class TaskCleanupTask extends Task
{
    /**
     * The minimum age (in seconds) of the task to be deleted.
     */
    const DEFAULT_CLEANUP_TASK_AGE = 3600;
    /**
     * The class name of the task.
     *
     * @var string
     */
    private $taskType;
    /**
     * A list of task statuses.
     *
     * @var array
     */
    private $taskStatuses;
    /**
     * An age of the task in seconds.
     *
     * @var int
     */
    private $taskAge;
    /**
     * Current progress.
     *
     * @var float
     */
    private $progress;

    /**
     * TaskCleanupTask constructor.
     *
     * @param string $taskType The type of the task to delete.
     * @param array $taskStatuses The list of queue item statuses.
     * @param string $taskAge The min age of the task.
     */
    public function __construct($taskType, array $taskStatuses, $taskAge)
    {
        $this->taskType = $taskType;
        $this->taskStatuses = $taskStatuses;
        $this->taskAge = $taskAge;
        $this->progress = 0.0;
    }

    /**
     * @inheritDoc
     */
    public static function fromArray(array $array)
    {
        return new static(
            $array['task_type'],
            !empty($array['task_statuses']) ? $array['task_statuses'] : array(),
            $array['task_age']
        );
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array(
            'task_type' => $this->taskType,
            'task_statuses' => $this->taskStatuses,
            'task_age' => $this->taskAge,
        );
    }

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return Serializer::serialize($this->toArray());
    }

    /**
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        list($this->taskType, $this->taskStatuses, $this->taskAge) = array_values(Serializer::unserialize($serialized));
    }

    /**
     * Retrieves task priority.
     *
     * @return int Task priority.
     */
    public function getPriority()
    {
        return Priority::LOW;
    }

    /**
     * Runs task logic.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function execute()
    {
        // cleanup requested tasks
        $this->cleanupTasks($this->taskType, $this->taskStatuses, $this->taskAge, 90);

        // self cleanup
        $this->cleanupTasks(static::getClassName(), array(QueueItem::COMPLETED), 3600, 10);

        $this->reportProgress(100);
    }

    /**
     * Cleans up the tasks with the specified parameters.
     *
     * @param string $taskType The type of the task to delete.
     * @param array $taskStatuses The list of queue item statuses.
     * @param string $taskAge The min age of the task.
     * @param int $progressPart Progress report part of the overall task.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    private function cleanupTasks($taskType, array $taskStatuses, $taskAge, $progressPart)
    {
        /** @var TimeProvider $timeProvider */
        $timeProvider = ServiceRegister::getService(TimeProvider::CLASS_NAME);
        $time = $timeProvider->getCurrentLocalTime()->getTimestamp();
        $filter = new QueryFilter();
        /** @noinspection PhpUnhandledExceptionInspection */
        $filter->where('taskType', Operators::EQUALS, $taskType)
            ->where('status', Operators::IN, $taskStatuses)
            ->where('lastUpdateTimestamp', Operators::LESS_OR_EQUAL_THAN, $time - $taskAge);

        $repository = RepositoryRegistry::getQueueItemRepository();
        $queueItems = $repository->select($filter);
        $totalItems = count($queueItems);
        if ($totalItems > 0) {
            $progressStep = $progressPart / $totalItems;

            foreach ($queueItems as $item) {
                $repository->delete($item);
                $this->progress += $progressStep;
                $this->reportProgress($this->progress);
            }
        }
    }
}
