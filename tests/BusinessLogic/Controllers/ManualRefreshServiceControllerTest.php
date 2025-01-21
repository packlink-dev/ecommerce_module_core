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

        $this->queueService = new TestQueueService();

        TestServiceRegister::registerService(
            QueueService::CLASS_NAME,
            function () {
                return $this->queueService;
            }
        );

        TestRepositoryRegistry::registerRepository(QueueItem::CLASS_NAME, MemoryQueueItemRepository::THIS_CLASS_NAME);

        $this->controller = new ManualRefreshServiceController();
    }

    /**
     * @return void
     */
    public function testEnqueueUpdateTaskSuccess(): void
    {
        $response = $this->controller->enqueueUpdateTask();

        $responseData = json_decode($response, true);

        self::assertEquals($responseData['status'], 'success');
    }

    /**
     * @return void
     */
    public function testEnqueueUpdateTaskFail(): void
    {
        $exception = new \RuntimeException('Failed to enqueue task.');
        $this->queueService->setExceptionResponse('enqueue', $exception);

        $response = $this->controller->enqueueUpdateTask();

        $responseData = json_decode($response, true);

        self::assertEquals($responseData['status'], 'error');
    }

    /**
     * @return void
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    public function testGetStatusQueued(): void
    {
        $this->controller->enqueueUpdateTask();

        $responseData = json_decode($this->controller->getTaskStatus(), true);

        self::assertEquals(QueueItem::QUEUED, $responseData['status']);
    }

    /**
     * @return void
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    public function testGetStatusFailed(): void
    {
        $this->controller->enqueueUpdateTask();

        $repo = RepositoryRegistry::getQueueItemRepository();
        $filter = new QueryFilter();
        $filter->where('taskType', Operators::EQUALS, 'UpdateShippingServicesTask');
        $filter->where('status', Operators::EQUALS, QueueItem::QUEUED);
        $queueItem = $repo->selectOne($filter);
        $queueItem->setStatus(QueueItem::FAILED);
        $repo->update($queueItem);

        $responseData = json_decode($this->controller->getTaskStatus(), true);

        self::assertEquals(QueueItem::FAILED, $responseData['status']);
    }

    public function testGetStatusItemNotQueued(): void
    {
        $responseData = json_decode($this->controller->getTaskStatus(), true);

        self::assertEquals(QueueItem::CREATED, $responseData['status']);
        self::assertEquals('Queue item not found.', $responseData['message']);
    }
}