<?php

namespace Logeecom\Tests\Infrastructure\ORM;

use DateTime;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\Serializer\Serializer;
use Logeecom\Infrastructure\TaskExecution\Interfaces\Priority;
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
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueueItemMassInsert()
    {
        $insertedIds = $this->insertQueueItems();
        foreach ($insertedIds as $id) {
            $this->assertGreaterThan(0, $id);
        }
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function testUpdate()
    {
        $this->insertQueueItems();
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
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryAllQueueItems()
    {
        $this->insertQueueItems();
        $repository = RepositoryRegistry::getQueueItemRepository();

        $this->assertCount($this->queueItemCount, $repository->select());
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithFiltersString()
    {
        $this->insertQueueItems();
        $repository = RepositoryRegistry::getQueueItemRepository();
        $queryFilter = new QueryFilter();
        $queryFilter->where('taskType', '=', 'FooTask');

        $this->assertCount($this->fooTasks, $repository->select($queryFilter));

        $queryFilter = new QueryFilter();
        $queryFilter->where('taskType', '!=', 'FooTask');
        $this->assertCount($this->queueItemCount - $this->fooTasks, $repository->select($queryFilter));
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithFiltersInt()
    {
        $this->insertQueueItems();
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
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function testQueryWithFiltersAndSort()
    {
        $this->insertQueueItems();
        $repository = RepositoryRegistry::getQueueItemRepository();
        $queryFilter = new QueryFilter();
        $queryFilter->where('queueTime', '<', DateTime::createFromFormat('Y-m-d', '2017-07-01'));
        $queryFilter->orderBy('queueTime', 'DESC');

        $results = $repository->select($queryFilter);
        $this->assertCount(10, $results);
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithFiltersAndLimit()
    {
        $this->insertQueueItems();
        $repository = RepositoryRegistry::getQueueItemRepository();
        $queryFilter = new QueryFilter();
        $queryFilter->where('queueTime', '<', DateTime::createFromFormat('Y-m-d', '2017-07-01'));
        $queryFilter->setLimit(5);

        $results = $repository->select($queryFilter);
        $this->assertCount(5, $results);
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testFindOldestQueuedItems()
    {
        $this->insertQueueItems();
        $repository = RepositoryRegistry::getQueueItemRepository();

        $this->assertCount(1, $repository->findOldestQueuedItems(Priority::NORMAL));
    }

    /**
     * @return void
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemSaveException
     */
    public function testSaveWithCondition()
    {
        $this->insertQueueItems();
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

        $item->setLastUpdateTimestamp(88888888);
        $exThrown = null;
        try {
            $repository->saveWithCondition($item, array('lastUpdateTimestamp' => 1493851325));
        } catch (\Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemSaveException $ex) {
            $exThrown = $ex;
        }

        $this->assertNotNull($exThrown);
    }

    /**
     * @return void
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemSaveException
     */
    public function testSaveWithConditionWithNull()
    {
        $this->insertQueueItems();
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

        $exThrown = null;
        try {
            $repository->saveWithCondition($item, array('status' => 'created', 'lastUpdateTimestamp' => 1518325751));
        } catch (\Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemSaveException $ex) {
            $exThrown = $ex;
        }

        $this->assertNotNull($exThrown);
    }

    /**
     * @return void
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testInvalidQueryFilter()
    {
        $repository = RepositoryRegistry::getQueueItemRepository();
        $queryFilter = new QueryFilter();
        $queryFilter->where('progress', '=', 20);

        $exThrown = null;
        try {
            $repository->select($queryFilter);
        } catch (\Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException $ex) {
            $exThrown = $ex;
        }

        $this->assertNotNull($exThrown);
    }

    /**
     * @before
     *
     * @return void
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     */
    protected function before()
    {
        $this->setUp();

        RepositoryRegistry::registerRepository(QueueItem::getClassName(), $this->getQueueItemEntityRepositoryClass());
    }

    /**
     * @after
     *
     * @return void
     */
    protected function after()
    {
        $this->cleanUpStorage();
        $this->tearDown();
    }

    /**
     * @return array
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function insertQueueItems()
    {
        $repository = RepositoryRegistry::getQueueItemRepository();
        $ids = array();
        foreach ($this->readQueueItemsFromFile() as $entity) {
            $ids[] = $repository->save($entity);
        }

        return $ids;
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
            $queueItem->setSerializedTask(Serializer::serialize($task));
            $queueItem->setCreateTimestamp($item['createTimestamp']);
            $queueItem->setQueueTimestamp($item['queueTimestamp']);
            $queueItem->setStartTimestamp($item['startTimestamp']);
            $queueItem->setLastUpdateTimestamp($item['lastUpdateTimestamp']);
            $queueItem->setFinishTimestamp($item['finishTimestamp']);
            $queueItem->setFailTimestamp($item['failTimestamp']);
            $queueItem->setPriority($item['priority']);

            $queueItems[] = $queueItem;
        }

        return $queueItems;
    }
}
