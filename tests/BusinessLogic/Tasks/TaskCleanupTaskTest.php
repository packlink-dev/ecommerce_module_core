<?php

namespace BusinessLogic\Tasks;

use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Infrastructure\TaskExecution\QueueService;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryQueueItemRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\FooTask;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestTaskRunnerWakeupService;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Logeecom\Infrastructure\Scheduler\Models\Schedule;
use Logeecom\Infrastructure\Scheduler\ScheduleCheckTask;
use Logeecom\Infrastructure\TaskExecution\TaskCleanupTask;

/**
 * Class TaskCleanupTaskTest.
 *
 * @package BusinessLogic\Tasks
 */
class TaskCleanupTaskTest extends BaseTestWithServices
{
    /**
     * @var \Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryQueueItemRepository
     */
    public $queueStorage;
    /**
     * @var \Logeecom\Infrastructure\TaskExecution\QueueService
     */
    public $queueService;

    /**
     * @before
     * @inheritdoc
     */
    protected function before()
    {
        parent::before();

        RepositoryRegistry::registerRepository(QueueItem::CLASS_NAME, MemoryQueueItemRepository::getClassName());
        RepositoryRegistry::registerRepository(Schedule::CLASS_NAME, MemoryRepository::getClassName());

        $this->queueStorage = RepositoryRegistry::getQueueItemRepository();

        TestServiceRegister::registerService(
            TaskRunnerWakeup::CLASS_NAME,
            function () {
                return new TestTaskRunnerWakeupService();
            }
        );

        $this->queueService = new QueueService();
        $me = $this;

        TestServiceRegister::registerService(
            QueueService::CLASS_NAME,
            function () use ($me) {
                return $me->queueService;
            }
        );
    }

    /**
     * Tests default removing of queue items.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\AbortTaskExecutionException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function testCleaningQueueItemsPerAge()
    {
        $this->timeProvider->setCurrentLocalTime(new \DateTime('now -2 days'));
        $oldItem = $this->queueService->enqueue('testQueue', new ScheduleCheckTask());
        $this->queueService->start($oldItem);
        $this->queueService->finish($oldItem);

        $this->timeProvider->setCurrentLocalTime(new \DateTime('now -2 minutes'));
        $youngItem = $this->queueService->enqueue('testQueue', new ScheduleCheckTask());
        $this->queueService->start($youngItem);
        $this->queueService->finish($youngItem);

        $this->timeProvider->setCurrentLocalTime(new \DateTime());
        $this->assertNotNull($this->queueService->find($youngItem->getId()), 'QueueItem should exist before cleanup.');
        $this->assertNotNull($this->queueService->find($oldItem->getId()), 'QueueItem should exist before cleanup.');

        $cleanupTask = new TaskCleanupTask(ScheduleCheckTask::getClassName(), array(QueueItem::COMPLETED), 3600);
        $cleanupTask->execute();

        $this->assertNotNull($this->queueService->find($youngItem->getId()), 'Young QueueItem must NOT be deleted.');
        $this->assertNull($this->queueService->find($oldItem->getId()), 'Old QueueItem must be deleted.');
    }

    /**
     * Tests default removing of queue items.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\AbortTaskExecutionException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function testCleaningQueueItemsPerStatus()
    {
        $this->timeProvider->setCurrentLocalTime(new \DateTime('now -2 days'));
        $inProgressItem = $this->queueService->enqueue('testQueue', new ScheduleCheckTask());
        $this->queueService->start($inProgressItem);

        $completed = $this->queueService->enqueue('testQueue', new ScheduleCheckTask());
        $this->queueService->start($completed);
        $this->queueService->finish($completed);

        $this->assertNotNull(
            $this->queueService->find($inProgressItem->getId()),
            'QueueItem should exist before cleanup.'
        );
        $this->assertNotNull(
            $this->queueService->find($completed->getId()),
            'QueueItem should exist before cleanup.'
        );

        $this->timeProvider->setCurrentLocalTime(new \DateTime());
        $cleanupTask = new TaskCleanupTask(ScheduleCheckTask::getClassName(), array(QueueItem::COMPLETED), 3600);
        $cleanupTask->execute();

        $this->assertNotNull(
            $this->queueService->find($inProgressItem->getId()),
            'Active QueueItem must NOT be deleted.'
        );
        $this->assertNull(
            $this->queueService->find($completed->getId()),
            'Old QueueItem must be deleted.'
        );
    }

    /**
     * Tests default removing of queue items.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\AbortTaskExecutionException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function testCleaningQueueItemsPerTaskType()
    {
        $this->timeProvider->setCurrentLocalTime(new \DateTime('now -2 days'));
        $targetItem = $this->queueService->enqueue('testQueue', new ScheduleCheckTask());
        $this->queueService->start($targetItem);

        $dummyItem = $this->queueService->enqueue('testQueue', new FooTask());
        $this->queueService->start($dummyItem);

        $this->assertNotNull(
            $this->queueService->find($targetItem->getId()),
            'QueueItem should exist before cleanup.'
        );
        $this->assertNotNull(
            $this->queueService->find($dummyItem->getId()),
            'QueueItem should exist before cleanup.'
        );

        $this->timeProvider->setCurrentLocalTime(new \DateTime());
        $cleanupTask = new TaskCleanupTask(ScheduleCheckTask::getClassName(), array(QueueItem::IN_PROGRESS), 3600);
        $cleanupTask->execute();

        $this->assertNotNull(
            $this->queueService->find($dummyItem->getId()),
            'QueueItem for non-targeted task must NOT be deleted.'
        );
        $this->assertNull(
            $this->queueService->find($targetItem->getId()),
            'QueueItem for targeted task must be deleted.'
        );
    }

    /**
     * Tests default removing of queue items.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\AbortTaskExecutionException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function testSelfCleanup()
    {
        $this->timeProvider->setCurrentLocalTime(new \DateTime('now -2 hours'));
        $cleanupTask = new TaskCleanupTask(ScheduleCheckTask::getClassName(), array(QueueItem::IN_PROGRESS), 3600);
        $targetItem = $this->queueService->enqueue('testQueue', $cleanupTask);
        $this->queueService->start($targetItem);
        $this->queueService->finish($targetItem);

        $cleanupTask = new TaskCleanupTask(ScheduleCheckTask::getClassName(), array(QueueItem::IN_PROGRESS), 3600);
        $targetItem = $this->queueService->enqueue('testQueue', $cleanupTask);
        $this->queueService->start($targetItem);
        $this->queueService->finish($targetItem);

        $queueRepository = RepositoryRegistry::getQueueItemRepository();

        $this->assertCount(2, $queueRepository->select(), 'Task cleanup should be in the queue.');

        $this->timeProvider->setCurrentLocalTime(new \DateTime());
        $cleanupTask = new TaskCleanupTask(ScheduleCheckTask::getClassName(), array(QueueItem::IN_PROGRESS), 3600);
        $targetItem = $this->queueService->enqueue('testQueue', $cleanupTask);

        $this->assertCount(3, $queueRepository->select());

        $this->queueService->start($targetItem);
        $this->queueService->finish($targetItem);

        $this->assertCount(1, $queueRepository->select(), 'Task cleanup should do self-cleanup.');
    }
}
