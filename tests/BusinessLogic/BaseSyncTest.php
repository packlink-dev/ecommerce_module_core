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
    public function before()
    {
        parent::before();

        $this->syncTask = $this->createSyncTaskInstance();
    }

    /**
     * Creates new instance of task that is being tested.
     *
     * @return Task
     */
    abstract protected function createSyncTaskInstance();


    /**
     * Pokrene business task i sakupi progress iz yield-ova.
     */
    protected function executeSyncTask()
    {
        foreach ($this->syncTask->execute() as $value) {
            // keep-alive je yield; -> $value je null
            if ($value !== null) {
                $this->eventHistory[] = $value;
            }
        }
    }

    /**
     * Validates whether tasks finished with 100%.
     * This method should be called after task executed.
     */

    protected function validate100Progress()
    {
        $last = end($this->eventHistory);

        $this->assertEquals(
            100,
            (int) round((float) $last),
            'Task must be successfully finished with 100% report progress.'
        );
    }

    /**
     * Attaches event listener to the sync task.
     */
    protected function attachProgressEventListener()
    {
        $me = $this;
        $this->syncTask->when(
            TaskProgressEvent::CLASS_NAME,
            function (TaskProgressEvent $event) use (&$me) {
                $me->eventHistory[] = $event;
            }
        );
    }
}
