<?php

namespace Logeecom\Tests\Infrastructure\TaskExecution;

use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Infrastructure\Utility\TimeProvider;
use Logeecom\Tests\Common\TestComponents\Utility\TestTimeProvider;
use Logeecom\Tests\Common\TestServiceRegister;
use Packlink\BusinessLogic\Tasks\UpdateShippingServicesTask;
use PHPUnit\Framework\TestCase;

/**
 * Class QueueItemEntityTest.
 *
 * @package Logeecom\Tests\Infrastructure\TaskExecution
 */
class QueueItemEntityTest extends TestCase
{
    /**
     * @var TimeProvider
     */
    protected $timeProvider;

    /**
     * @throws \Exception
     */
    protected function setUp()
    {
        parent::setUp();

        $timeProvider = $this->timeProvider = new TestTimeProvider();

        new TestServiceRegister(
            array(
                TimeProvider::CLASS_NAME => function () use ($timeProvider) {
                    return $timeProvider;
                },
            )
        );
    }

    /**
     *
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    public function testToArray()
    {
        $createdTime = time();
        $startTime = time() + 1;
        $finishTime = time() + 2;
        $failTime = time() + 3;
        $earliestTime = time() + 4;
        $queueTime = time() + 5;
        $lastUpdateTime = time() + 6;

        $entity = new QueueItem();
        $entity->setId(1234);
        $entity->setStatus(QueueItem::COMPLETED);
        $entity->setContext('context');
        $entity->setSerializedTask(serialize(new UpdateShippingServicesTask()));
        $entity->setQueueName('queue');
        $entity->setLastExecutionProgressBasePoints(2541);
        $entity->setProgressBasePoints(458);
        $entity->setRetries(5);
        $entity->setFailureDescription('failure');
        $entity->setCreateTimestamp($createdTime);
        $entity->setStartTimestamp($startTime);
        $entity->setFinishTimestamp($finishTime);
        $entity->setFailTimestamp($failTime);
        $entity->setEarliestStartTimestamp($earliestTime);
        $entity->setQueueTimestamp($queueTime);
        $entity->setLastUpdateTimestamp($lastUpdateTime);

        $data = $entity->toArray();

        self::assertEquals($data['id'], $entity->id);
        self::assertEquals($data['status'], $entity->getStatus());
        self::assertEquals($data['context'], $entity->getContext());
        self::assertEquals($data['serializedTask'], $entity->getSerializedTask());
        self::assertEquals($data['queueName'], $entity->getQueueName());
        self::assertEquals($data['lastExecutionProgressBasePoints'], $entity->getLastExecutionProgressBasePoints());
        self::assertEquals($data['progressBasePoints'], $entity->getProgressBasePoints());
        self::assertEquals($data['retries'], $entity->getRetries());
        self::assertEquals($data['failureDescription'], $entity->getFailureDescription());
        self::assertEquals($data['createTime'], $this->timeProvider->getDateTime($createdTime));
        self::assertEquals($data['startTime'], $this->timeProvider->getDateTime($startTime));
        self::assertEquals($data['finishTime'], $this->timeProvider->getDateTime($finishTime));
        self::assertEquals($data['failTime'], $this->timeProvider->getDateTime($failTime));
        self::assertEquals($data['earliestStartTime'], $this->timeProvider->getDateTime($earliestTime));
        self::assertEquals($data['queueTime'], $this->timeProvider->getDateTime($queueTime));
        self::assertEquals($data['lastUpdateTime'], $this->timeProvider->getDateTime($lastUpdateTime));

        $task = $entity->getTask();
        self::assertNotNull($task);
        self::assertInstanceOf('\\Packlink\\BusinessLogic\\Tasks\\UpdateShippingServicesTask', $task);
    }

    public function testFromArrayAndToJSON()
    {
        $createdTime = $this->timeProvider->getDateTime(time());
        $startTime = $this->timeProvider->getDateTime(time() + 1);
        $finishTime = $this->timeProvider->getDateTime(time() + 2);
        $failTime = $this->timeProvider->getDateTime(time() + 3);
        $earliestTime = $this->timeProvider->getDateTime(time() + 4);
        $queueTime = $this->timeProvider->getDateTime(time() + 5);
        $lastUpdateTime = $this->timeProvider->getDateTime(time() + 6);

        $data = array(
            'id' => 123,
            'status' => QueueItem::COMPLETED,
            'context' => 'context',
            'serializedTask' => serialize(new UpdateShippingServicesTask()),
            'queueName' => 'queue',
            'lastExecutionProgressBasePoints' => 1234,
            'progressBasePoints' => 7345,
            'retries' => 2,
            'failureDescription' => 'failure',
            'createTime' => $createdTime,
            'startTime' => $startTime,
            'finishTime' => $finishTime,
            'failTime' => $failTime,
            'earliestStartTime' => $earliestTime,
            'queueTime' => $queueTime,
            'lastUpdateTime' => $lastUpdateTime,
        );

        $entity = QueueItem::fromArray($data);

        self::assertEquals($data['id'], $entity->id);
        self::assertEquals($data['status'], $entity->getStatus());
        self::assertEquals($data['context'], $entity->getContext());
        self::assertEquals($data['serializedTask'], $entity->getSerializedTask());
        self::assertEquals($data['queueName'], $entity->getQueueName());
        self::assertEquals($data['lastExecutionProgressBasePoints'], $entity->getLastExecutionProgressBasePoints());
        self::assertEquals($data['progressBasePoints'], $entity->getProgressBasePoints());
        self::assertEquals($data['retries'], $entity->getRetries());
        self::assertEquals($data['failureDescription'], $entity->getFailureDescription());
        self::assertEquals($data['createTime'], $createdTime);
        self::assertEquals($data['startTime'], $startTime);
        self::assertEquals($data['finishTime'], $finishTime);
        self::assertEquals($data['failTime'], $failTime);
        self::assertEquals($data['earliestStartTime'], $earliestTime);
        self::assertEquals($data['queueTime'], $queueTime);
        self::assertEquals($data['lastUpdateTime'], $lastUpdateTime);

        self::assertEquals(json_encode($data), json_encode($entity->toArray()));
    }
}
