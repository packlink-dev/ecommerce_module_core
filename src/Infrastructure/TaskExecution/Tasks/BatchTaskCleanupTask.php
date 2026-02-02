<?php

namespace Logeecom\Infrastructure\TaskExecution\Tasks;

use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ORM\Contracts\ConditionallyDeletes;
use Logeecom\Infrastructure\ORM\Exceptions\EntityClassException;
use Logeecom\Infrastructure\ORM\QueryFilter\Operators;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\Serializer\Serializer;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Exceptions\AbortTaskExecutionException;
use Logeecom\Infrastructure\TaskExecution\Task;
use Logeecom\Infrastructure\Utility\TimeProvider;

/**
 * Class BatchTaskCleanupTask
 *
 * Task cleans a list of provided tasks that are within the list of provided statuses.
 *
 * It uses configurable value max task age to narrow down the list of deleted tasks.
 *
 * @package Logeecom\Infrastructure\TaskExecution
 */
class BatchTaskCleanupTask extends Task
{
    const NUMBER_OF_TASKS = 5000;
    const NUMBER_OF_ITERATIONS = 20;
    /**
     * @var array
     */
    protected $taskStatuses;
    /**
     * @var array
     */
    protected $taskTypes;

    /**
     * BatchTaskCleanupTask constructor.
     *
     * @param array $taskStatuses
     * @param array $taskTypes
     */
    public function __construct(array $taskStatuses, array $taskTypes = array())
    {
        $this->taskStatuses = $taskStatuses;
        $this->taskTypes = $taskTypes;
    }

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return Serializer::serialize(array($this->taskStatuses, $this->taskTypes));
    }

    /**
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        list($this->taskStatuses, $this->taskTypes) = Serializer::unserialize($serialized);
    }

    /**
     * Transforms serializable object into an array.
     *
     * @return array Array representation of a serializable object.
     */
    public function toArray()
    {
        return array(
            'taskStatuses' => $this->taskStatuses,
            'taskTypes' => $this->taskTypes,
        );
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
        return new static($array['taskStatuses'], $array['taskTypes']);
    }

    /**
     * @inheritDoc
     */
    public function __serialize()
    {
        return $this->toArray();
    }

    /**
     * @inheritDoc
     */
    public function __unserialize($data)
    {
        $this->taskStatuses = $data['taskStatuses'];
        $this->taskTypes = $data['taskTypes'];
    }

    /**
     * Executes the task.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\AbortTaskExecutionException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws EntityClassException
     */
    public function execute()
    {
        Logger::logDebug('Task types to be deleted:', 'Core', $this->taskTypes);

        $repository = RepositoryRegistry::getQueueItemRepository();
        if (!$repository instanceof ConditionallyDeletes) {
            throw new AbortTaskExecutionException(
                'QueueItemRepository must implement ConditionallyDeletes '
                . 'interface before it can utilize BatchTaskCleanupTask.'
            );
        }

        $query = $this->getQuery();
        $numberOfTasks = $repository->count($query);

        $this->reportProgress(10);

        $this->deleteInBatches($numberOfTasks, $query);

        $this->reportProgress(100);
    }

    /**
     * @param $numberOfTasks
     * @param QueryFilter $queryFilter
     *
     * @return void
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function deleteInBatches($numberOfTasks, QueryFilter $queryFilter)
    {
        $repository = RepositoryRegistry::getQueueItemRepository();
        $queryFilter->setLimit(self::NUMBER_OF_TASKS);
        $i = 0;

        while ($numberOfTasks > 0 && $i < self::NUMBER_OF_ITERATIONS) {
            $repository->deleteWhere($queryFilter);

            $numberOfTasks -= self::NUMBER_OF_TASKS;
            $i++;

            $this->reportProgress(10 + ($i + 1) * 3);
        }
    }

    /**
     * Retrieves query that will be used identify tasks that must be deleted.
     *
     * @return \Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    private function getQuery()
    {
        $query = new QueryFilter();
        $query->where('queueTime', Operators::LESS_THAN, $this->getAgeCutOff());
        $query->where('status', Operators::IN, $this->taskStatuses);

        if (!empty($this->taskTypes)) {
            $query->where('taskType', Operators::IN, $this->getTaskTypes());
        }

        return $query;
    }

    /**
     * Retrieves date time before which tasks will be deleted.
     *
     * @return \DateTime
     */
    private function getAgeCutOff()
    {
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $maxAge = $this->getConfigService()->getMaxTaskAge();
        $currentDateTime = $this->getTimeProvider()->getDateTime(time());

        return $currentDateTime->modify("-$maxAge day");
    }

    /**
     * Retrieves list of task types that must be deleted.
     *
     * @return array
     */
    private function getTaskTypes()
    {
        return array_unique(array_merge($this->taskTypes, array($this->getType())));
    }

    /**
     * Retrieves time provider.
     *
     * @return TimeProvider | object
     */
    private function getTimeProvider()
    {
        return ServiceRegister::getService(TimeProvider::CLASS_NAME);
    }
}
