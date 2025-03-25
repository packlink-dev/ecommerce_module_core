<?php

namespace Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution;

use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Infrastructure\TaskExecution\QueueService;
use Logeecom\Infrastructure\TaskExecution\Task;

class TestQueueService extends QueueService
{
    private $callHistory = array();
    private $exceptionResponses = array();

    public function getMethodCallHistory($methodName)
    {
        return !empty($this->callHistory[$methodName]) ? $this->callHistory[$methodName] : array();
    }

    public function setExceptionResponse($methodName, $exceptionToThrow)
    {
        $this->exceptionResponses[$methodName] = $exceptionToThrow;
    }

    public function requeue(QueueItem $queueItem)
    {
        if (!empty($this->exceptionResponses['requeue'])) {
            throw $this->exceptionResponses['requeue'];
        }

        $this->callHistory['requeue'][] = array('queueItem' => $queueItem);

        parent::requeue($queueItem);
    }

    public function fail(QueueItem $queueItem, $failureDescription)
    {
        if (!empty($this->exceptionResponses['fail'])) {
            throw $this->exceptionResponses['fail'];
        }

        $this->callHistory['fail'][] = array('queueItem' => $queueItem, 'failureDescription' => $failureDescription);
        parent::fail($queueItem, $failureDescription);
    }

    public function find($id)
    {
        if (!empty($this->exceptionResponses['find'])) {
            throw $this->exceptionResponses['find'];
        }

        $this->callHistory['find'][] = array('id' => $id);

        return parent::find($id);
    }

    public function start(QueueItem $queueItem)
    {
        if (!empty($this->exceptionResponses['start'])) {
            throw $this->exceptionResponses['start'];
        }

        $this->callHistory['start'][] = array('queueItem' => $queueItem);
        parent::start($queueItem);
    }

    public function finish(QueueItem $queueItem)
    {
        if (!empty($this->exceptionResponses['finish'])) {
            throw $this->exceptionResponses['finish'];
        }

        $this->callHistory['finish'][] = array('queueItem' => $queueItem);
        parent::finish($queueItem);
    }

    /**
     * Creates queue item for given task, enqueues in queue with given name and starts it
     *
     * @param $queueName
     * @param Task $task
     *
     * @param int $progress
     * @param int $lastExecutionProgress
     *
     * @return QueueItem
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    public function generateRunningQueueItem($queueName, Task $task, $progress = 0, $lastExecutionProgress = 0)
    {
        $queueItem = $this->enqueue($queueName, $task);
        $queueItem->setProgressBasePoints($progress);
        $queueItem->setLastExecutionProgressBasePoints($lastExecutionProgress);
        $this->start($queueItem);

        return $queueItem;
    }

    /**
     * Enqueues queue item to a given queue and stores changes.
     *
     * @param string $queueName Name of a queue where queue item should be queued.
     * @param Task $task Task to enqueue.
     * @param string $context Task execution context. If integration supports multiple accounts (middleware
     *     integration) context based on account id should be provided. Failing to do this will result in global task
     *     context and unpredictable task execution.
     *
     * @param int | null $priority Null priority falls back to Priority::NORMAL
     *
     * @return \Logeecom\Infrastructure\TaskExecution\QueueItem Created queue item.
     *
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException When queue storage
     *      fails to save the item.
     */
    public function enqueue($queueName, Task $task, $context = '', $priority = null)
    {
        if (!empty($this->exceptionResponses['enqueue'])) {
            throw $this->exceptionResponses['enqueue'];
        }

        return parent::enqueue($queueName, $task, $context, $priority);
    }
}