<?php

namespace Logeecom\Infrastructure\TaskExecution\Model;

class TaskStatus
{
    /**
     * Task with the given type/context does not exist in the system.
     */
    const NOT_FOUND = 'not_found';

    /**
     * Task has been created/scheduled but is not yet eligible to run (e.g. delayed).
     */
    const SCHEDULED = 'scheduled';

    /**
     * Task is waiting in a queue / scheduler to be picked up for execution.
     */
    const PENDING = 'pending';

    /**
     * Task is currently executing.
     */
    const RUNNING = 'running';

    /**
     * Task finished successfully.
     */
    const COMPLETED = 'completed';

    /**
     * Task finished with an error and will not continue (or retry policy exhausted).
     */
    const FAILED = 'failed';

    /**
     * Task execution was intentionally stopped (manual cancel, system abort, etc.).
     */
    const CANCELED = 'canceled';

    /**
     * Task is in retry/backoff window (optional but useful when you introduce retries/backoff).
     */
    const RETRYING = 'retrying';

    const CREATED = 'created';

    /**
     * Current task status.
     *
     * @var string
     */
    private $status;

    /**
     * Optional failure or informational message associated with the task.
     *
     * @var string|null
     */
    private $message;

    /**
     * TaskStatus constructor.
     *
     * @param string      $status  Current execution status of the task.
     * @param string|null $message Optional failure or status message.
     */
    public function __construct(string $status, $message = null)
    {
        $this->status = $status;
        $this->message = $message;
    }


    /**
     * Returns the current task status.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Returns the optional task message.
     *
     * @return string|null
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Returns all supported task-level statuses.
     *
     * @return string[]
     */
    public static function supportedStatuses(): array
    {
        return array(
            self::SCHEDULED,
            self::PENDING,
            self::RUNNING,
            self::COMPLETED,
            self::FAILED,
            self::CANCELED,
            self::RETRYING,
        );
    }
}