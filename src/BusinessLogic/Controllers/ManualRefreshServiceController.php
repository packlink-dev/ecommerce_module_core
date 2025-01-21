<?php

namespace Packlink\BusinessLogic\Controllers;

use Logeecom\Infrastructure\ORM\QueryFilter\Operators;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Infrastructure\TaskExecution\QueueService;
use Logeecom\Infrastructure\Utility\TimeProvider;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Tasks\UpdateShippingServicesTask;

class ManualRefreshServiceController
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
     * @return string
     */
    public function enqueueUpdateTask(): string
    {
        $queueService = ServiceRegister::getService(QueueService::class);

        $configService = $this->getConfigService();

        try {
            $queueService->enqueue(
                $configService->getDefaultQueueName(),
                new UpdateShippingServicesTask(),
                $configService->getContext()
            );

            return json_encode(['status' => 'success', 'message' => 'Task successfully enqueued.']);
        } catch (\Exception $e) {
            return json_encode(['status' => 'error', 'message' => 'Failed to enqueue task: ' . $e->getMessage()]);
        }
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
    public function getTaskStatus($context = '')
    {
        $repo = RepositoryRegistry::getQueueItemRepository();

        $filter = $this->buildCondition($context);
        $filter->orderBy('queueTime', 'DESC');

        $item = $repo->selectOne($filter);
        if ($item) {
            $status = $item->getStatus();

            if ($status === QueueItem::FAILED || $status === QueueItem::COMPLETED) {
                return $status === QueueItem::FAILED
                    ? json_encode(['status' => $status, 'message' => $item->getFailureDescription()])
                    : json_encode(['status' => $status, 'message' => 'Queue item completed']);
            }

            return json_encode(['status' => $status]);
        }

        return json_encode(['status' => QueueItem::CREATED, 'message' => 'Queue item not found.']);
    }

    /**
     * Builds query condition.
     *
     * @param string $context
     *
     * @return \Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    protected function buildCondition($context = '')
    {
        $filter = new QueryFilter();
        $filter->where('taskType', Operators::EQUALS, 'UpdateShippingServicesTask');
        if (!empty($context)) {
            $filter->where('context', Operators::EQUALS, $context);
        }

        return $filter;
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