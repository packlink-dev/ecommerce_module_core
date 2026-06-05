<?php

namespace Packlink\DemoUI\Controllers;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\Exceptions\BaseException;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Packlink\BusinessLogic\Controllers\ShippingMethodController;
use Packlink\BusinessLogic\UpdateShippingServices\Interfaces\UpdateShippingServicesOrchestratorInterface;
use Packlink\BusinessLogic\UpdateShippingServices\Interfaces\UpdateShippingServiceTaskStatusServiceInterface;

class ManualRefreshController extends BaseHttpController
{
    public function enqueueUpdateTask()
    {
        // The orchestrator skips enqueuing when the latest status is created/queued/in_progress.
        // Since the demo runs tasks synchronously, a leftover non-terminal status would deadlock
        // the refresh forever. Clear it so the task is actually (re)run.
        $this->resetTaskStatus();

        $controller = $this->getCoreController();

        $this->output($controller->enqueueUpdateTask()->toArray());
    }

    /**
     * Removes any existing update-shipping-services task status for the current context,
     * so the orchestrator's "already pending" guard does not block a fresh run.
     */
    private function resetTaskStatus()
    {
        /** @var Configuration $config */
        $config = ServiceRegister::getService(Configuration::CLASS_NAME);
        /** @var UpdateShippingServiceTaskStatusServiceInterface $statusService */
        $statusService = ServiceRegister::getService(UpdateShippingServiceTaskStatusServiceInterface::class);

        $context = (string)$config->getContext();

        // Delete all statuses for this context (upsert keeps one, but loop defensively).
        for ($i = 0; $i < 50; $i++) {
            $entity = $statusService->getLatestByContext($context);
            if (!$entity) {
                break;
            }

            $statusService->delete($entity);
        }
    }

    public function getTaskStatus()
    {
        $shippingController = new ShippingMethodController();

        sleep(2);

        if (count($shippingController->getAll()) > 0) {
            $this->output(array('status' => QueueItem::COMPLETED, 'message' => 'Queue item completed'));

            return;
        }

        try {
            $status = $this->getCoreController()->getTaskStatus()->toArray();
        } catch (BaseException $e) {
            $status = array('status' => QueueItem::FAILED, 'message' => $e->getMessage());
        }

        $this->output($status);
    }

    /**
     * Builds the core ManualRefreshController with its required dependencies.
     *
     * @return \Packlink\BusinessLogic\Controllers\ManualRefreshController
     */
    private function getCoreController()
    {
        /** @var UpdateShippingServiceTaskStatusServiceInterface $statusService */
        $statusService = ServiceRegister::getService(UpdateShippingServiceTaskStatusServiceInterface::class);
        /** @var UpdateShippingServicesOrchestratorInterface $orchestrator */
        $orchestrator = ServiceRegister::getService(UpdateShippingServicesOrchestratorInterface::class);

        return new \Packlink\BusinessLogic\Controllers\ManualRefreshController($statusService, $orchestrator);
    }
}
