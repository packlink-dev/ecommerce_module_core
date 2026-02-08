<?php

namespace Packlink\BusinessLogic\Controllers;

use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskExecutorInterface;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskStatusProviderInterface;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Controllers\DTO\TaskStatus;
use Packlink\BusinessLogic\Tasks\BusinessTasks\UpdateShippingServicesBusinessTask;
use Logeecom\Infrastructure\TaskExecution\Model\TaskStatus as CoreTaskStatus;

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
     * Enqueues the UpdateShippingServicesBusinessTask and returns a JSON response.
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

        /** @var CoreTaskStatus $result */
        $result = $this->statusProvider->getLatestStatus(
            'UpdateShippingServicesBusinessTask',
            $context
        );

        if ($result->getStatus() === CoreTaskStatus::NOT_FOUND) {
            $taskStatus->status = CoreTaskStatus::CREATED;
            $taskStatus->message = 'Item not found in queue. Task was not enqueued yet.';
            return $taskStatus;
        }

        $taskStatus->status = $result->getStatus();
        $taskStatus->message = $result->getMessage();


        if ($result->getStatus() === CoreTaskStatus::FAILED) {
            $taskStatus->message = $result->getMessage() ?: 'Task failed.';
        }

        if ($result->getStatus() === CoreTaskStatus::COMPLETED) {
            $taskStatus->message = 'Task completed successfully.';
        }

        return $taskStatus;
    }

    /**
     * Configuration service instance.
     *
     * @return Configuration
     */
    protected function getConfigService(): Configuration
    {
        if ($this->configuration === null) {
            $this->configuration = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configuration;
    }
}
