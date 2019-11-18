<?php

namespace Logeecom\Tests\Infrastructure\TaskExecution;

use Logeecom\Infrastructure\Serializer\Concrete\NativeSerializer;
use Logeecom\Infrastructure\Serializer\Serializer;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Infrastructure\Utility\TimeProvider;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\FooTask;
use Logeecom\Tests\Infrastructure\Common\TestComponents\Utility\TestTimeProvider;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use PHPUnit\Framework\TestCase;

/**
 * Class QueueItemTest
 *
 * @package Logeecom\Tests\Infrastructure\TaskExecution
 */
class QueueItemTest extends TestCase
{
    /** @var \Logeecom\Tests\Infrastructure\Common\TestComponents\Utility\TestTimeProvider */
    private $timeProvider;

    /**
     * @throws \Exception
     */
    protected function setUp()
    {
        $timeProvider = new TestTimeProvider();

        new TestServiceRegister(
            array(
                TimeProvider::CLASS_NAME => function () use ($timeProvider) {
                    return $timeProvider;
                },
                Serializer::CLASS_NAME => function() {
                    return new NativeSerializer();
                }
            )
        );

        $this->timeProvider = $timeProvider;
    }

    /**
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     * @throws \Exception
     */
    public function testWhenQueueItemIsCreatedItShouldBeInCreatedStatus()
    {
        $task = new FooTask();
        $queueItem = new QueueItem($task);

        $this->assertEquals(
            QueueItem::CREATED,
            $queueItem->getStatus(),
            'When created queue item must set status to "created".'
        );
        $this->assertEquals(
            $task->getType(),
            $queueItem->getTaskType(),
            'When created queue item must set record task type.'
        );
        $this->assertNull($queueItem->getId(), 'When created queue item should not be in storage. Id must be null.');
        $this->assertNull(
            $queueItem->getQueueName(),
            'When created queue should not be in storage. Queue name must be null.'
        );
        $this->assertSame(
            0,
            $queueItem->getLastExecutionProgressBasePoints(),
            'When created queue item must set last execution progress to 0.'
        );
        $this->assertSame(0, $queueItem->getProgressBasePoints(), 'When created queue item must set progress to 0.');
        $this->assertSame(0, $queueItem->getRetries(), 'When created queue item must set retries to 0.');
        $this->assertSame(
            '',
            $queueItem->getFailureDescription(),
            'When created queue item must set failure description to empty string.'
        );
        $this->assertEquals(
            Serializer::serialize($task),
            $queueItem->getSerializedTask(),
            'When created queue item must record given task.'
        );
        $this->assertSame(
            $this->timeProvider->getCurrentLocalTime()->getTimestamp(),
            $queueItem->getCreateTimestamp(),
            'When created queue item must record create time.'
        );
        $this->assertNull($queueItem->getQueueTimestamp(), 'When created queue item must set queue time to null.');
        $this->assertNull($queueItem->getStartTimestamp(), 'When created queue item must set start time to null.');
        $this->assertNull($queueItem->getFinishTimestamp(), 'When created queue item must set finish time to null.');
        $this->assertNull($queueItem->getFailTimestamp(), 'When created queue item must set fail time to null.');
        $this->assertNull(
            $queueItem->getEarliestStartTimestamp(),
            'When created queue item must set earliest start time to null.'
        );
    }

    /**
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    public function testItShouldBePossibleToCreateQueueItemWithSerializedTask()
    {
        $task = new FooTask('test task', 123);
        $queueItem = new QueueItem();

        $queueItem->setSerializedTask(Serializer::serialize($task));

        /** @var FooTask $actualTask */
        $actualTask = $queueItem->getTask();
        $this->assertSame($task->getDependency1(), $actualTask->getDependency1());
        $this->assertSame($task->getDependency2(), $actualTask->getDependency2());
    }

    /**
     * @expectedException \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    public function testQueueItemShouldThrowExceptionWhenSerializationFails()
    {
        $task = new FooTask('test task', 123);
        $queueItem = new QueueItem();

        $queueItem->setSerializedTask('invalid serialized task content');

        /** @var \Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\FooTask $actualTask */
        $actualTask = $queueItem->getTask();
        $this->assertSame($task->getDependency1(), $actualTask->getDependency1());
        $this->assertSame($task->getDependency2(), $actualTask->getDependency2());
    }

    /**
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    public function testItShouldUpdateTaskWhenSettingSerializedTask()
    {
        $newTask = new FooTask('new task', 123);
        $queueItem = new QueueItem(new FooTask('initial task', 1));

        $queueItem->setSerializedTask(Serializer::serialize($newTask));

        /** @var \Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\FooTask $actualTask */
        $actualTask = $queueItem->getTask();
        $this->assertSame(
            'new task',
            $actualTask->getDependency1(),
            'Setting serialized task must update task instance.'
        );
        $this->assertSame(123, $actualTask->getDependency2(), 'Setting serialized task must update task instance.');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid QueueItem status: "Not supported". Status must be one of "created", "queued",
     *     "in_progress", "completed" or "failed" values.
     */
    public function testItShouldNotBePossibleToSetNotSupportedStatus()
    {
        $queueItem = new QueueItem();

        $queueItem->setStatus('Not supported');

        $this->fail('Setting not supported status should fail.');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Last execution progress percentage must be value between 0 and 100.
     */
    public function testItShouldNotBePossibleToSetNegativeLastExecutionProgress()
    {
        $queueItem = new QueueItem();

        $queueItem->setLastExecutionProgressBasePoints(-1);

        $this->fail('QueueItem must refuse setting negative last execution progress with InvalidArgumentException.');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Last execution progress percentage must be value between 0 and 100.
     */
    public function testItShouldNotBePossibleToSetMoreThan10000ForLastExecutionProgress()
    {
        $queueItem = new QueueItem();

        $queueItem->setLastExecutionProgressBasePoints(10001);

        $this->fail(
            'QueueItem must refuse setting greater than 100 last execution progress values with InvalidArgumentException.'
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Progress percentage must be value between 0 and 100.
     */
    public function testItShouldNotBePossibleToSetNegativeProgress()
    {
        $queueItem = new QueueItem();

        $queueItem->setProgressBasePoints(-1);

        $this->fail('QueueItem must refuse setting negative progress with InvalidArgumentException.');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Progress percentage must be value between 0 and 100.
     */
    public function testItShouldNotBePossibleToSetMoreThan100ForProgress()
    {
        $queueItem = new QueueItem();

        $queueItem->setProgressBasePoints(10001);

        $this->fail('QueueItem must refuse setting greater than 100 progress values with InvalidArgumentException.');
    }

    public function testItShouldBePossibleToGetFormattedProgressValue()
    {
        $queueItem = new QueueItem();

        $queueItem->setProgressBasePoints(2548);

        $this->assertSame(
            25.48,
            $queueItem->getProgressFormatted(),
            'Formatted progress should be string representation of progress percentage rounded to two decimals.'
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Progress percentage must be value between 0 and 100.
     */
    public function testItShouldNotBePossibleToReportNonIntegerValueForProgress()
    {
        $queueItem = new QueueItem();

        $queueItem->setProgressBasePoints('50%');

        $this->fail('QueueItem must refuse setting non integer progress values with InvalidArgumentException.');
    }

    public function testItShouldBePossibleToSetTaskExecutionContext()
    {
        $queueItem = new QueueItem();

        $queueItem->setContext('test');

        $this->assertSame('test', $queueItem->getContext(), 'Queue item must return proper task execution context.');
    }

    /**
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    public function testQueueItemIdToTask()
    {
        $task = new FooTask('test task', 123);
        $queueItem = new QueueItem($task);
        $queueItem->setId(27);

        self::assertEquals(27, $task->getExecutionId());

        /** @var FooTask $actualTask */
        $actualTask = $queueItem->getTask();
        self::assertEquals(27, $actualTask->getExecutionId());
    }

    /**
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    public function testQueueItemIdToSerializedTask()
    {
        $task = new FooTask('test task', 123);
        $queueItem = new QueueItem();
        $queueItem->setId(27);

        $queueItem->setSerializedTask(Serializer::serialize($task));

        /** @var \Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\FooTask $actualTask */
        $actualTask = $queueItem->getTask();

        self::assertEquals(27, $actualTask->getExecutionId());
    }
}
