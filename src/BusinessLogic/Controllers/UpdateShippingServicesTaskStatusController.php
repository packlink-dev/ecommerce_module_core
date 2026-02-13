<?php

namespace Packlink\BusinessLogic\Controllers;

use Logeecom\Infrastructure\ORM\QueryFilter\Operators;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskStatusProviderInterface;
use Logeecom\Infrastructure\TaskExecution\Model\TaskStatus;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Infrastructure\Utility\TimeProvider;
use Packlink\BusinessLogic\UpdateShippingServices\Interfaces\UpdateShippingServiceTaskStatusServiceInterface;
use Packlink\BusinessLogic\UpdateShippingServices\Models\UpdateShippingServiceTaskStatus;

/**
 * Class UpdateShippingServicesTaskStatusController.
 *
 * @package Packlink\BusinessLogic\Controllers
 */
class UpdateShippingServicesTaskStatusController
{
    /**
     * @var UpdateShippingServiceTaskStatusServiceInterface
     */
    private $statusService;

    /**
     * @param UpdateShippingServiceTaskStatusServiceInterface $statusService
     */
    public function __construct(
        UpdateShippingServiceTaskStatusServiceInterface $statusService
    ) {
        $this->statusService = $statusService;
    }

    /**
     * Checks the status of the task responsible for getting services.
     *
     * @param string $context
     *
     * @return string <p>One of the following statuses:
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
    public function getLastTaskStatus($context = '')
    {
        /** @var UpdateShippingServiceTaskStatus|null $entity */
        $entity = $this->statusService->getLatestByContext((string)$context);

        if (!$entity) {
            return TaskStatus::NOT_FOUND;
        }

        return $entity->getStatus();
    }
}
