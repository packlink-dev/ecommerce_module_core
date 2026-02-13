<?php

namespace Packlink\BusinessLogic\Controllers;

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Controllers\DTO\TaskStatus;
use Logeecom\Infrastructure\TaskExecution\Model\TaskStatus as CoreTaskStatus;
use Packlink\BusinessLogic\UpdateShippingServices\Interfaces\UpdateShippingServicesOrchestratorInterface;
use Packlink\BusinessLogic\UpdateShippingServices\Interfaces\UpdateShippingServiceTaskStatusServiceInterface;
use Packlink\BusinessLogic\UpdateShippingServices\Models\UpdateShippingServiceTaskStatus;

class ManualRefreshController
{
    /**
     * @var UpdateShippingServiceTaskStatusServiceInterface $statusService
     */
    private $statusService;

    /**
     * @var UpdateShippingServicesOrchestratorInterface $orchestrator
     */
    private $orchestrator;

    public function __construct(
        UpdateShippingServiceTaskStatusServiceInterface $statusService,
        UpdateShippingServicesOrchestratorInterface $orchestrator
    )
    {
        $this->statusService = $statusService;
        $this->orchestrator = $orchestrator;
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
        $context = $this->getConfigService()->getContext();


        try {
            $this->orchestrator->enqueue($context);

            $taskStatus->status = TaskStatus::SUCCESS;
            $taskStatus->message = 'Task successfully enqueued.';
            return $taskStatus;

        } catch (\Exception $e) {
            $this->statusService->upsertStatus($context, CoreTaskStatus::FAILED, $e->getMessage(), true);

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

        /** @var UpdateShippingServiceTaskStatus|null $entity */
        $entity = $this->statusService->getLatestByContext((string)$context);

        if (!$entity) {
            $taskStatus->status = CoreTaskStatus::NOT_FOUND; // ili CoreTaskStatus::CREATED
            $taskStatus->message = 'Status not found. Task was not enqueued yet.';
            return $taskStatus;
        }

        $taskStatus->status = $entity->getStatus();

        if ($entity->getStatus() === CoreTaskStatus::FAILED) {
            $taskStatus->message = $entity->getError() ?: 'Task failed.';
            return $taskStatus;
        }

        if ($entity->getStatus() === CoreTaskStatus::COMPLETED) {
            $taskStatus->message = 'Task completed successfully.';
            return $taskStatus;
        }

        $taskStatus->message = $entity->getError() ?: $entity->getStatus();

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
