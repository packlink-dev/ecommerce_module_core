<?php

namespace Packlink\BusinessLogic\UpdateShippingServices;

use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskExecutorInterface;
use Logeecom\Infrastructure\TaskExecution\Model\TaskStatus;
use Packlink\BusinessLogic\Tasks\BusinessTasks\UpdateShippingServicesBusinessTask;
use Packlink\BusinessLogic\UpdateShippingServices\Interfaces\UpdateShippingServiceTaskStatusServiceInterface;
use Packlink\BusinessLogic\UpdateShippingServices\Models\UpdateShippingServiceTaskStatus;

class UpdateShippingServicesOrchestrator implements Interfaces\UpdateShippingServicesOrchestratorInterface
{
    /**
     * @var TaskExecutorInterface
     */
    private $taskExecutor;

    /**
     * @var UpdateShippingServiceTaskStatusServiceInterface
     */
    private $statusService;

    public function __construct(
        TaskExecutorInterface $taskExecutor,
        UpdateShippingServiceTaskStatusServiceInterface $statusService
    ) {
        $this->taskExecutor = $taskExecutor;
        $this->statusService = $statusService;
    }

    /**
     * @inheritDoc
     */
    public function enqueue($context)
    {
        /** @var UpdateShippingServiceTaskStatus|null $entity */
        $entity = $this->statusService->getLatestByContext($context);

        if ($entity) {
            $currentStatus = $entity->getStatus();

            if (in_array($currentStatus, [
                TaskStatus::RUNNING,
                TaskStatus::SCHEDULED,
                TaskStatus::PENDING,
                'created',
                'queued',
                'in_progress'
            ], true)) {
                return;
            }
        }

        $this->statusService->upsertStatus(
            $context,
            TaskStatus::CREATED
        );

        try {
            $this->taskExecutor->enqueue(
                new UpdateShippingServicesBusinessTask()
            );
        } catch (\Exception $e) {

            $this->statusService->upsertStatus(
                $context,
                TaskStatus::FAILED,
                $e->getMessage(),
                true
            );

            throw $e;
        }
    }
}