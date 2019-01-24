<?php

namespace Logeecom\Tests\BusinessLogic;

use Logeecom\Infrastructure\TaskExecution\Task;
use Logeecom\Infrastructure\TaskExecution\TaskEvents\TaskProgressEvent;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;

/**
 * Class BaseSyncTest.
 *
 * @package Logeecom\Tests\Infrastructure
 */
abstract class BaseSyncTest extends BaseTestWithServices
{
    /**
     * Tested task instance.
     *
     * @var Task
     */
    public $syncTask;
    /**
     * History of events from task.
     *
     * @var array
     */
    public $eventHistory;
    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->syncTask = $this->createSyncTaskInstance();
        $me = $this;
        $this->syncTask->when(
            TaskProgressEvent::CLASS_NAME,
            function (TaskProgressEvent $event) use (&$me) {
                $me->eventHistory[] = $event;
            }
        );
    }

    /**
     * Validates whether tasks finished with 100%.
     * This method should be called after task executed.
     */
    protected function validate100Progress()
    {
        /** @var TaskProgressEvent $lastReportProgress */
        $lastReportProgress = end($this->eventHistory);

        $this->assertEquals(
            100,
            $lastReportProgress->getProgressFormatted(),
            'Task must be successfully finished with 100% report progress.'
        );
    }

    /**
     * Creates new instance of task that is being tested.
     *
     * @return Task
     */
    abstract protected function createSyncTaskInstance();
}