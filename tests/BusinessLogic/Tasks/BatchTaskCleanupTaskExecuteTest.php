<?php

namespace Logeecom\Tests\BusinessLogic\Tasks;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Infrastructure\TaskExecution\Task;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\ORM\MemoryQueueItemReposiotoryWithConditionalDelete;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryQueueItemRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\BarTask;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\FooTask;
use Packlink\BusinessLogic\Tasks\BatchTaskCleanupTask;

class BatchTaskCleanupTaskExecuteTest extends BaseTestWithServices
{
    /**
     * @before
     * @inheritDoc
     */
    protected function before()
    {
        parent::before();

        RepositoryRegistry::registerRepository(
            QueueItem::getClassName(),
            MemoryQueueItemReposiotoryWithConditionalDelete::getClassName()
        );

        $this->repoSetup();
    }

    public function testTaskAgeCutOff()
    {
        // arrange
        $task = new BatchTaskCleanupTask(
            array(QueueItem::COMPLETED, QueueItem::IN_PROGRESS),
            array('FooTask', 'BarTask')
        );

        // act
        $task->execute();
        // arrange
        $task = new BatchTaskCleanupTask(
            array(QueueItem::COMPLETED, QueueItem::IN_PROGRESS),
            array('FooTask', 'BarTask')
        );

        // act
        $task->execute();

        // assert
        $tasks = $this->getQueueItemRepo()->select();
        $this->assertCount(1, $tasks);
        $item = $tasks[0];
        $this->assertInstanceOf(
            '\Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\FooTask',
            $item->getTask()
        );
        // assert
        $tasks = $this->getQueueItemRepo()->select();
        $this->assertCount(1, $tasks);
        $item = $tasks[0];
        $this->assertInstanceOf(
            '\Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\FooTask',
            $item->getTask()
        );
    }

    public function testTasksOlderThanAgeCutOff()
    {
        // arrange
        $task = new BatchTaskCleanupTask(
            array(QueueItem::COMPLETED, QueueItem::IN_PROGRESS),
            array('FooTask', 'BarTask')
        );
        $this->getConfigService()->setMaxTaskAge(1);

        // act
        $task->execute();

        // assert
        $tasks = $this->getQueueItemRepo()->select();
        $this->assertEmpty($tasks);
    }

    public function testTasksYungerThenAgeCutOff()
    {
        // arrange
        $task = new BatchTaskCleanupTask(
            array(QueueItem::COMPLETED, QueueItem::IN_PROGRESS),
            array('FooTask', 'BarTask')
        );
        $this->getConfigService()->setMaxTaskAge(20);

        // act
        $task->execute();

        // assert
        $tasks = $this->getQueueItemRepo()->select();
        $this->assertCount(2, $tasks);
    }

    public function testRemoveSpecificTaskType()
    {
        // arrange
        $task = new BatchTaskCleanupTask(array(QueueItem::COMPLETED, QueueItem::IN_PROGRESS), array('FooTask'));
        $this->getConfigService()->setMaxTaskAge(1);

        // act
        $task->execute();

        // assert
        $tasks = $this->getQueueItemRepo()->select();
        $this->assertCount(1, $tasks);
        $item = $tasks[0];
        $this->assertInstanceOf(
            '\Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\BarTask',
            $item->getTask()
        );
    }

    public function testTaskWithSpecificStatus()
    {
        // arrange
        $task = new BatchTaskCleanupTask(array(QueueItem::COMPLETED), array('FooTask', 'BarTask'));
        $this->getConfigService()->setMaxTaskAge(1);

        // act
        $task->execute();

        // assert
        $tasks = $this->getQueueItemRepo()->select();
        $this->assertCount(1, $tasks);
        $item = $tasks[0];
        $this->assertInstanceOf(
            '\Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\BarTask',
            $item->getTask()
        );
    }

    public function testNoTaskTypesProvided()
    {
        // arrange
        $task = new BatchTaskCleanupTask(array(QueueItem::COMPLETED, QueueItem::IN_PROGRESS));
        $this->getConfigService()->setMaxTaskAge(1);

        // act
        $task->execute();

        // assert
        $tasks = $this->getQueueItemRepo()->select();
        $this->assertEmpty($tasks);
    }

    /**
     * @expectedException \Logeecom\Infrastructure\TaskExecution\Exceptions\AbortTaskExecutionException
     */
    public function testRepositoryDoesNotImplementConditionallyDeletes()
    {
        // arrange
        RepositoryRegistry::registerRepository(QueueItem::getClassName(), MemoryQueueItemRepository::getClassName());
        $task = new BatchTaskCleanupTask(array(QueueItem::COMPLETED, QueueItem::IN_PROGRESS));

        // act
        $task->execute();
    }

    private function getTaskSet()
    {
        $time = new \DateTime();

        return array(
            $this->instantiateQueueItem(new FooTask(), $time->modify('-2 day'), QueueItem::COMPLETED),
            $this->instantiateQueueItem(new BarTask(), $time->modify('-10 day'), QueueItem::IN_PROGRESS),
        );
    }

    private function instantiateQueueItem(Task $task, \DateTime $queueTime, $status)
    {
        $item = new QueueItem($task);
        $item->setQueueTimestamp($queueTime->getTimestamp());
        $item->setStatus($status);

        return $item;
    }

    private function repoSetup()
    {
        $repo = $this->getQueueItemRepo();
        foreach ($this->getTaskSet() as $item) {
            $repo->save($item);
        }
    }

    private function getQueueItemRepo()
    {
        return RepositoryRegistry::getRepository(QueueItem::getClassName());
    }

    /**
     * @return \Packlink\BusinessLogic\Configuration | object
     */
    private function getConfigService()
    {
        return ServiceRegister::getService(Configuration::CLASS_NAME);
    }
}
