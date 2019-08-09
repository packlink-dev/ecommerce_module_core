<?php

namespace Logeecom\Tests\Infrastructure\ORM;

use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\BarTask;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\FooTask;
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

        $id = $queueItem->getId();
        $queueItem->setQueueName('Test' . $queueItem->getQueueName());
        $repository->update($queueItem);

        $queryFilter = new QueryFilter();
        $queryFilter->where('queueName', '=', $queueItem->getQueueName());
        $queueItem = $repository->selectOne($queryFilter);
        $this->assertEquals($id, $queueItem->getId());

        $queueItem->setQueueName(substr($queueItem->getQueueName(), 4));
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
        $queryFilter->where('lastExecutionProgress', '>', 0);

        $this->assertCount(23, $repository->select($queryFilter));

        $queryFilter = new QueryFilter();
        $queryFilter->where('lastExecutionProgress', '<', 10000);

        $this->assertCount(37, $repository->select($queryFilter));

        $queryFilter->where('lastExecutionProgress', '>', 0);
        $this->assertCount(10, $repository->select($queryFilter));
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
        $queryFilter->where('queueTime', '<', \DateTime::createFromFormat('Y-m-d', '2017-07-01'));
        $queryFilter->orderBy('queueTime', 'DESC');

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
        $queryFilter->where('queueTime', '<', \DateTime::createFromFormat('Y-m-d', '2017-07-01'));
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

        $this->assertCount(2, $repository->findOldestQueuedItems());
        $this->assertTrue(true);
    }

    /**
     * @depends testQueueItemMassInsert
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemSaveException
     */
    public function testSaveWithCondition()
    {
        $repository = RepositoryRegistry::getQueueItemRepository();
        $queryFilter = new QueryFilter();
        $queryFilter->where('lastUpdateTimestamp', '=', 1493851325);

        /** @var QueueItem $item */
        $item = $repository->selectOne($queryFilter);
        $this->assertNotNull($item);

        $item->setLastUpdateTimestamp(99999999);
        $id = $repository->saveWithCondition($item, array('lastUpdateTimestamp' => 1493851325));

        $this->assertEquals($item->getId(), $id);

        $queryFilter = new QueryFilter();
        $queryFilter->where('lastUpdateTimestamp', '=', 99999999);

        /** @var QueueItem $item */
        $item = $repository->selectOne($queryFilter);
        $this->assertNotNull($item);

        $this->setExpectedException('\Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemSaveException');
        $item->setLastUpdateTimestamp(88888888);
        $repository->saveWithCondition($item, array('lastUpdateTimestamp' => 1493851325));
    }

    /**
     * @depends testQueueItemMassInsert
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemSaveException
     */
    public function testSaveWithConditionWithNull()
    {
        $repository = RepositoryRegistry::getQueueItemRepository();
        $queryFilter = new QueryFilter();
        $queryFilter->where('lastUpdateTimestamp', '=', 1518325751);

        /** @var QueueItem $item */
        $item = $repository->selectOne($queryFilter);
        $this->assertNotNull($item);

        $item->setLastUpdateTimestamp(null);

        $id = $repository->saveWithCondition($item, array('status' => 'created', 'lastUpdateTimestamp' => 1518325751));
        $this->assertEquals($item->getId(), $id);

        $id = $repository->saveWithCondition($item, array('status' => 'created', 'lastUpdateTimestamp' => null));
        $this->assertEquals($item->getId(), $id);

        $this->setExpectedException('\Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemSaveException');
        $repository->saveWithCondition($item, array('status' => 'created', 'lastUpdateTimestamp' => 1518325751));
    }

    /**
     * @expectedException \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testInvalidQueryFilter()
    {
        $repository = RepositoryRegistry::getQueueItemRepository();
        $queryFilter = new QueryFilter();
        $queryFilter->where('progress', '=', 20);

        $repository->select($queryFilter);
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
        $json = file_get_contents(__DIR__ . '/../Common/EntityData/QueueItems.json');
        $queueItemsRaw = json_decode($json, true);
        foreach ($queueItemsRaw as $item) {
            if ($item['taskType'] === 'FooTask') {
                $task = new FooTask($item['serializedTask'], $item['progress']);
            } else {
                $task = new BarTask();
            }

            $queueItem = new QueueItem();
            $queueItem->setStatus($item['status']);
            $queueItem->setQueueName($item['queueName']);
            $queueItem->setProgressBasePoints($item['progress']);
            $queueItem->setLastExecutionProgressBasePoints($item['lastExecutionProgress']);
            $queueItem->setRetries($item['retries']);
            $queueItem->setFailureDescription($item['failureDescription']);
            $queueItem->setSerializedTask(serialize($task));
            $queueItem->setCreateTimestamp($item['createTimestamp']);
            $queueItem->setQueueTimestamp($item['queueTimestamp']);
            $queueItem->setStartTimestamp($item['startTimestamp']);
            $queueItem->setLastUpdateTimestamp($item['lastUpdateTimestamp']);
            $queueItem->setFinishTimestamp($item['finishTimestamp']);
            $queueItem->setFailTimestamp($item['failTimestamp']);

            $queueItems[] = $queueItem;
        }

        return $queueItems;
    }
}
