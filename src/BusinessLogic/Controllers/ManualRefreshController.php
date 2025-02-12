<?php

namespace Packlink\BusinessLogic\Controllers;

use Logeecom\Infrastructure\ORM\QueryFilter\Operators;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Infrastructure\TaskExecution\QueueService;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Controllers\DTO\TaskStatus;
use Packlink\BusinessLogic\Tasks\UpdateShippingServicesTask;

class ManualRefreshController
{
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
    public function enqueueUpdateTask()
    {
        $queueService = ServiceRegister::getService(QueueService::CLASS_NAME);

        $configService = $this->getConfigService();

        try {
            $queueService->enqueue(
                $configService->getDefaultQueueName(),
                new UpdateShippingServicesTask(),
                $configService->getContext()
            );

            $taskStatus = new TaskStatus();
            $taskStatus->status = TaskStatus::SUCCESS;
            $taskStatus->message = 'Task successfully enqueued.';
            return $taskStatus;

        } catch (QueueStorageUnavailableException $e) {
            $taskStatus = new TaskStatus();
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
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    public function getTaskStatus($context = '')
    {
        /**@var QueueService $service */
        $service = ServiceRegister::getService(QueueService::CLASS_NAME);

        $item = $service->findLatestByType('UpdateShippingServicesTask', $context);

        $taskStatus = new TaskStatus();

        if ($item) {
            $status = $item->getStatus();
            $taskStatus->status = $status;

            if ($status === QueueItem::FAILED) {
                $taskStatus->message = $item->getFailureDescription();
            }

            if ($status === QueueItem::COMPLETED) {
                $taskStatus->message = 'Queue item completed';
            }

            return $taskStatus;
        }

        $taskStatus->status = QueueItem::CREATED;
        $taskStatus->message = 'Queue item not found.';

        return $taskStatus;
    }

    /**
     * Returns an instance of configuration service.
     *
     * @return \Packlink\BusinessLogic\Configuration Configuration service.
     */
    protected function getConfigService()
    {
        if ($this->configuration === null) {
            $this->configuration = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configuration;
    }

}