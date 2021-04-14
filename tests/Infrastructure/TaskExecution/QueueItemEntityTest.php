<?php

namespace Logeecom\Tests\Infrastructure\TaskExecution;

use Logeecom\Infrastructure\Serializer\Concrete\NativeSerializer;
use Logeecom\Infrastructure\Serializer\Serializer;
use Logeecom\Infrastructure\TaskExecution\Interfaces\Priority;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Infrastructure\Utility\TimeProvider;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\FooTask;
use Logeecom\Tests\Infrastructure\Common\TestComponents\Utility\TestTimeProvider;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
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

    protected $serializer;

    /**
     * @throws \Exception
     */
    protected function setUp()
    {
        parent::setUp();

        $timeProvider = $this->timeProvider = new TestTimeProvider();
        $serializer = $this->serializer = new NativeSerializer();

        new TestServiceRegister(
            array(
                TimeProvider::CLASS_NAME => function () use ($timeProvider) {
                    return $timeProvider;
                },
                Serializer::CLASS_NAME => function () use ($serializer) {
                    return $serializer;
                }
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
        $entity->setSerializedTask(Serializer::serialize(new FooTask()));
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

        self::assertEquals($data['id'], $entity->getId());
        self::assertEquals($data['status'], $entity->getStatus());
        self::assertEquals($data['context'], $entity->getContext());
        self::assertEquals($data['serializedTask'], $entity->getSerializedTask());
        self::assertEquals($data['queueName'], $entity->getQueueName());
        self::assertEquals($data['lastExecutionProgressBasePoints'], $entity->getLastExecutionProgressBasePoints());
        self::assertEquals($data['progressBasePoints'], $entity->getProgressBasePoints());
        self::assertEquals($data['retries'], $entity->getRetries());
        self::assertEquals($data['failureDescription'], $entity->getFailureDescription());
        self::assertEquals($data['createTime'], $this->timeProvider->getDateTime($createdTime)->format(DATE_ATOM));
        self::assertEquals($data['startTime'], $this->timeProvider->getDateTime($startTime)->format(DATE_ATOM));
        self::assertEquals($data['finishTime'], $this->timeProvider->getDateTime($finishTime)->format(DATE_ATOM));
        self::assertEquals($data['failTime'], $this->timeProvider->getDateTime($failTime)->format(DATE_ATOM));
        self::assertEquals(
            $data['earliestStartTime'],
            $this->timeProvider->getDateTime($earliestTime)->format(DATE_ATOM)
        );
        self::assertEquals($data['queueTime'], $this->timeProvider->getDateTime($queueTime)->format(DATE_ATOM));
        self::assertEquals(
            $data['lastUpdateTime'],
            $this->timeProvider->getDateTime($lastUpdateTime)->format(DATE_ATOM)
        );

        $task = $entity->getTask();
        self::assertNotNull($task);
        self::assertInstanceOf(
            '\\Logeecom\\Tests\\Infrastructure\\Common\\TestComponents\\TaskExecution\\FooTask',
            $task
        );
    }

    public function testFromArrayAndToJSON()
    {
        $tz = date_default_timezone_get();
        date_default_timezone_set('UTC');

        $createdTime = $this->timeProvider->getDateTime(time());
        $startTime = $this->timeProvider->getDateTime(time() + 1);
        $finishTime = $this->timeProvider->getDateTime(time() + 2);
        $failTime = $this->timeProvider->getDateTime(time() + 3);
        $earliestTime = $this->timeProvider->getDateTime(time() + 4);
        $queueTime = $this->timeProvider->getDateTime(time() + 5);
        $lastUpdateTime = $this->timeProvider->getDateTime(time() + 6);

        $data = array(
            'class_name' => QueueItem::CLASS_NAME,
            'id' => 123,
            'status' => QueueItem::COMPLETED,
            'context' => 'context',
            'serializedTask' => Serializer::serialize(new FooTask()),
            'queueName' => 'queue',
            'lastExecutionProgressBasePoints' => 1234,
            'progressBasePoints' => 7345,
            'retries' => 2,
            'failureDescription' => 'failure',
            'createTime' => $createdTime->format(DATE_ATOM),
            'startTime' => $startTime->format(DATE_ATOM),
            'finishTime' => $finishTime->format(DATE_ATOM),
            'failTime' => $failTime->format(DATE_ATOM),
            'earliestStartTime' => $earliestTime->format(DATE_ATOM),
            'queueTime' => $queueTime->format(DATE_ATOM),
            'lastUpdateTime' => $lastUpdateTime->format(DATE_ATOM),
            'priority' => Priority::LOW,
            'brand' => '',
        );

        $entity = QueueItem::fromArray($data);

        self::assertEquals($data['id'], $entity->getId());
        self::assertEquals($data['status'], $entity->getStatus());
        self::assertEquals($data['context'], $entity->getContext());
        self::assertEquals($data['serializedTask'], $entity->getSerializedTask());
        self::assertEquals($data['queueName'], $entity->getQueueName());
        self::assertEquals($data['lastExecutionProgressBasePoints'], $entity->getLastExecutionProgressBasePoints());
        self::assertEquals($data['progressBasePoints'], $entity->getProgressBasePoints());
        self::assertEquals($data['retries'], $entity->getRetries());
        self::assertEquals($data['failureDescription'], $entity->getFailureDescription());
        self::assertEquals($createdTime->getTimestamp(), $entity->getCreateTimestamp());
        self::assertEquals($startTime->getTimestamp(), $entity->getStartTimestamp());
        self::assertEquals($finishTime->getTimestamp(), $entity->getFinishTimestamp());
        self::assertEquals($failTime->getTimestamp(), $entity->getFailTimestamp());
        self::assertEquals($earliestTime->getTimestamp(), $entity->getEarliestStartTimestamp());
        self::assertEquals($queueTime->getTimestamp(), $entity->getQueueTimestamp());
        self::assertEquals($lastUpdateTime->getTimestamp(), $entity->getLastUpdateTimestamp());

        self::assertEquals(json_encode($data), json_encode($entity->toArray()));

        date_default_timezone_set($tz);
    }
}
