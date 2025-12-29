<?php

namespace Packlink\BusinessLogic\Controllers;

use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskExecutorInterface;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskStatusProviderInterface;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Infrastructure\TaskExecution\QueueService;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Controllers\DTO\TaskStatus;
use Packlink\BusinessLogic\Tasks\BusinessTasks\UpdateShippingServicesBusinessTask;
use Packlink\BusinessLogic\Tasks\UpdateShippingServicesTask;

class ManualRefreshController
{

    /**
     * @var TaskExecutorInterface
     */
    private $taskExecutor;

    /**
     * @var TaskStatusProviderInterface
     */
    private $statusProvider;

    public function __construct(
        TaskExecutorInterface       $taskExecutor,
        TaskStatusProviderInterface $statusProvider
    )
    {
        $this->taskExecutor = $taskExecutor;
        $this->statusProvider = $statusProvider;
    }

    /**
     * Configuration service instance.
     *
     * @var Configuration
     */
    protected $configuration;

    /**
     * Enqueues the UpdateShippingServicesTask and returns a JSON response.
     *
     * @return TaskStatus
     */
    public function enqueueUpdateTask(): TaskStatus
    {
        $taskStatus = new TaskStatus();

        try {
            $this->taskExecutor->enqueue(new UpdateShippingServicesBusinessTask());

            $taskStatus->status = TaskStatus::SUCCESS;
            $taskStatus->message = 'Task successfully enqueued.';
            return $taskStatus;

        } catch (QueueStorageUnavailableException $e) {
            $taskStatus->status = TaskStatus::ERROR;
            $taskStatus->message = 'Failed to enqueue task: ' . $e->getMessage();
            return $taskStatus;
        }
    }

    /**
     * Checks the status of the task responsible for getting services.
     *
     * @param string $context
     *
     * @return TaskStatus <p>One of the following statuses:
     *  QueueItem::FAILED - when the task failed,
     *  QueueItem::COMPLETED - when the task completed successfully,
     *  QueueItem::IN_PROGRESS - when the task is in progress,
     *  QueueItem::QUEUED - when the default warehouse is not set by user and the task was not enqueued.
     * </p>
     *
     */
    public function getTaskStatus($context = '')
    {
        $taskStatus = new TaskStatus();

        $result = $this->statusProvider->getLatestStatus(
            UpdateShippingServicesBusinessTask::class,
            $context
        );

        if (empty($result)) {
            $taskStatus->status = QueueItem::CREATED;
            $taskStatus->message = 'Queue item not found.';
            return $taskStatus;
        }

        $taskStatus->status = $result['status'];
        $taskStatus->message = $result['message'] ?? null;


        if ($result['status'] === QueueItem::FAILED) {
            $taskStatus->message = $result['message'] ?: 'Task failed.';
        }

        if ($result['status'] === QueueItem::COMPLETED) {
            $taskStatus->message = 'Queue item completed';
        }

        return $taskStatus;
    }
}