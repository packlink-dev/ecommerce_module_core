<?php

namespace BusinessLogic\Controllers;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\ORM\QueryFilter\Operators;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Infrastructure\TaskExecution\QueueService;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\TestShopConfiguration;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryQueueItemRepository;

use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\TestRepositoryRegistry;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestQueueService;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Controllers\ManualRefreshServiceController;

class ManualRefreshServiceControllerTest extends BaseTestWithServices
{

    /** @var Configuration */
    public $configuration;

    /**
     * @var ManualRefreshServiceController
     */
    public $controller;

    /**
     * @var TestQueueService
     */
    public $queueService;
    /**
     * @before
     * @inheritdoc
     */
    public function before()
    {
        parent::before();

        $configuration = new TestShopConfiguration();

        TestServiceRegister::registerService(
            Configuration::CLASS_NAME,
            function () use ($configuration) {
                return $configuration;
            }
        );

        $queueService = new TestQueueService();
        $this->queueService = $queueService;

        TestServiceRegister::registerService(
            QueueService::CLASS_NAME,
            function () use ($queueService) {
                return $queueService;
            }
        );

        TestRepositoryRegistry::registerRepository(QueueItem::CLASS_NAME, MemoryQueueItemRepository::THIS_CLASS_NAME);

        $this->controller = new ManualRefreshServiceController();
    }

    /**
     * @return void
     */
    public function testEnqueueUpdateTaskSuccess()
    {
        $response = $this->controller->enqueueUpdateTask();

        self::assertEquals($response['status'], 'success');
    }

    /**
     * @return void
     */
    public function testEnqueueUpdateTaskFail()
    {
        $exception = new \RuntimeException('Failed to enqueue task.');
        $this->queueService->setExceptionResponse('enqueue', $exception);

        $response = $this->controller->enqueueUpdateTask();

        self::assertEquals($response['status'], 'error');
    }

    /**
     * @return void
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    public function testGetStatusQueued()
    {
        $this->controller->enqueueUpdateTask();

        $response = $this->controller->getTaskStatus();

        self::assertEquals(QueueItem::QUEUED, $response['status']);
    }

    /**
     * @return void
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    public function testGetStatusFailed()
    {
        $this->controller->enqueueUpdateTask();

        $repo = RepositoryRegistry::getQueueItemRepository();
        $filter = new QueryFilter();
        $filter->where('taskType', Operators::EQUALS, 'UpdateShippingServicesTask');
        $filter->where('status', Operators::EQUALS, QueueItem::QUEUED);
        $queueItem = $repo->selectOne($filter);
        $queueItem->setStatus(QueueItem::FAILED);
        $repo->update($queueItem);

        $response = $this->controller->getTaskStatus();

        self::assertEquals(QueueItem::FAILED, $response['status']);
    }

    public function testGetStatusItemNotQueued()
    {
        $responseData = $this->controller->getTaskStatus();

        self::assertEquals(QueueItem::CREATED, $responseData['status']);
        self::assertEquals('Queue item not found.', $responseData['message']);
    }
}