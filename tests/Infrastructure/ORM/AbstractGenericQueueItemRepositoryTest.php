<?php

namespace Logeecom\Tests\Infrastructure\ORM;

use Logeecom\Infrastructure\ORM\Entities\QueueItem;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use PHPUnit\Framework\TestCase;

/**
 * Class AbstractGenericTest
 * @package Logeecom\Tests\Infrastructure\ORM
 */
abstract class AbstractGenericQueueItemRepositoryTest extends TestCase
{
    protected $queueItemCount = 50;
    protected $fooTasks = 19;

    /**
     * @return string
     */
    abstract public function getQueueItemEntityRepositoryClass();

    /**
     * Cleans up all storage services used by repositories
     */
    abstract public function cleanUpStorage();

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testRegisteredRepositories()
    {
        $queueItemRepo = RepositoryRegistry::getQueueItemRepository();
        $this->assertInstanceOf(
            "\\Logeecom\\Infrastructure\\ORM\\Interfaces\\QueueItemRepository",
            $queueItemRepo,
            'QueueItem repository must be instance of QueueItemRepository'
        );
    }

    /**
     * @depends testRegisteredRepositories
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueueItemMassInsert()
    {
        $repository = RepositoryRegistry::getQueueItemRepository();

        foreach ($this->readQueueItemsFromFile() as $entity) {
            $id = $repository->save($entity);
            $this->assertGreaterThan(0, $id);
        }
    }

    /**
     * @depends testQueueItemMassInsert
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function testUpdate()
    {
        $repository = RepositoryRegistry::getQueueItemRepository();
        $queryFilter = new QueryFilter();
        $queryFilter->where('taskType', '=', 'FooTask');
        /** @var QueueItem $queueItem */
        $queueItem = $repository->selectOne($queryFilter);

        $id = $queueItem->id;
        $queueItem->taskType = 'Test' . $queueItem->taskType;
        $repository->update($queueItem);

        $queryFilter = new QueryFilter();
        $queryFilter->where('taskType', '=', 'TestFooTask');
        $queueItem = $repository->selectOne($queryFilter);
        $this->assertEquals($id, $queueItem->getId());

        $queueItem->taskType = 'FooTask';
        $repository->update($queueItem);
    }

    /**
     * @depends testQueueItemMassInsert
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryAllQueueItems()
    {
        $repository = RepositoryRegistry::getQueueItemRepository();

        $this->assertCount($this->queueItemCount, $repository->select());
    }

    /**
     * @depends testQueueItemMassInsert
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithFiltersString()
    {
        $repository = RepositoryRegistry::getQueueItemRepository();
        $queryFilter = new QueryFilter();
        $queryFilter->where('taskType', '=', 'FooTask');

        $this->assertCount($this->fooTasks, $repository->select($queryFilter));

        $queryFilter = new QueryFilter();
        $queryFilter->where('taskType', '!=', 'FooTask');
        $this->assertCount($this->queueItemCount - $this->fooTasks, $repository->select($queryFilter));
    }

    /**
     * @depends testQueueItemMassInsert
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithFiltersInt()
    {
        $repository = RepositoryRegistry::getQueueItemRepository();
        $queryFilter = new QueryFilter();
        $queryFilter->where('progress', '<', 10000);
        $queryFilter->where('progress', '>', 0);

        $this->assertCount(50, $repository->select($queryFilter));
    }

    /**
     * @depends testQueueItemMassInsert
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function testQueryWithFiltersAndSort()
    {
        $repository = RepositoryRegistry::getQueueItemRepository();
        $queryFilter = new QueryFilter();
        $queryFilter->where('queueTimestamp', '<', \DateTime::createFromFormat('Y-m-d', '2017-07-01'));
        $queryFilter->orderBy('queueTimestamp', 'DESC');

        $results = $repository->select($queryFilter);
        $this->assertCount(10, $results);
    }

    /**
     * @depends testQueueItemMassInsert
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithFiltersAndLimit()
    {
        $repository = RepositoryRegistry::getQueueItemRepository();
        $queryFilter = new QueryFilter();
        $queryFilter->where('queueTimestamp', '<', \DateTime::createFromFormat('Y-m-d', '2017-07-01'));
        $queryFilter->setLimit(5);

        $results = $repository->select($queryFilter);
        $this->assertCount(5, $results);
    }

    /**
     * @depends testQueueItemMassInsert
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testFindOldestQueuedItems()
    {
        $repository = RepositoryRegistry::getQueueItemRepository();

        $this->assertCount(2, $repository->findOldestQueuedItems(10));
        $this->assertTrue(true);
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     */
    protected function setUp()
    {
        parent::setUp();

        RepositoryRegistry::registerRepository(QueueItem::getClassName(), $this->getQueueItemEntityRepositoryClass());
    }

    protected function tearDown()
    {
        $this->cleanUpStorage();
        parent::tearDown();
    }

    /**
     * Reads test data fixtures about queue items from file
     *
     * @return QueueItem[]
     */
    protected function readQueueItemsFromFile()
    {
        $queueItems = array();
        $json = file_get_contents(__DIR__ . '/../../Common/EntityData/QueueItems.json');
        $queueItemsRaw = json_decode($json, true);
        foreach ($queueItemsRaw as $item) {
            $queueItem = new QueueItem();
            $queueItem->status = $item['status'];
            $queueItem->taskType = $item['taskType'];
            $queueItem->queueName = $item['queueName'];
            $queueItem->progress = $item['progress'];
            $queueItem->lastExecutionProgress = $item['lastExecutionProgress'];
            $queueItem->retries = $item['retries'];
            $queueItem->failureDescription = $item['failureDescription'];
            $queueItem->serializedTask = $item['serializedTask'];
            $queueItem->createTimestamp = $this->createDateTime($item['createTimestamp']);
            $queueItem->queueTimestamp = $this->createDateTime($item['queueTimestamp']);
            $queueItem->startTimestamp = $this->createDateTime($item['startTimestamp']);
            $queueItem->lastUpdateTimestamp = $this->createDateTime($item['lastUpdateTimestamp']);
            $queueItem->finishTimestamp = $this->createDateTime($item['finishTimestamp']);
            $queueItem->failTimestamp = $this->createDateTime($item['failTimestamp']);

            $queueItems[] = $queueItem;
        }

        return $queueItems;
    }

    /**
     * @param int $ts
     *
     * @return \DateTime | null
     */
    private function createDateTime($ts)
    {
        $dateTime = null;
        if ($ts) {
            try {
                $dateTime = new \DateTime();
            } catch (\Exception $e) {
                return null;
            }
            $dateTime->setTimestamp($ts);
        }

        return $dateTime;
    }
}
