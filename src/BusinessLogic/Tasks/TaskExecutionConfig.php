<?php

namespace Packlink\BusinessLogic\Tasks;

class TaskExecutionConfig
{
    /**
     * Queue name for task grouping.
     *
     * Used by HTTP executor to determine QueueService queue.
     * Can be used by other executors for logical grouping.
     *
     * @var string
     */
    private $queueName;

    /**
     * Task execution priority (0-100).
     *
     * Higher priority tasks execute first.
     * Used by HTTP executor, can be used by other executors.
     *
     * @var int
     */
    private $priority;

    /**
     * Execution context (shop ID, tenant ID, etc.).
     *
     * Used for multi-tenant scenarios.
     * Used by HTTP executor, can be used by other executors.
     *
     * @var string
     */
    private $context;

    /**
     * TaskExecutionConfig constructor.
     *
     * @param string $queueName Queue name.
     * @param int $priority Task priority (0-100).
     * @param string|null $context Execution context.
     */
    public function __construct(string $queueName, int $priority, $context = '')
    {
        $this->queueName = $queueName;
        $this->priority = $priority;
        $this->context = $context;
    }

    /**
     * Get queue name.
     *
     * @return string Queue name.
     */
    public function getQueueName(): string
    {
        return $this->queueName;
    }

    /**
     * Get priority.
     *
     * @return int Priority (0-100).
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * Get context.
     *
     * @return string|null
     * Context (shop ID, tenant ID, etc.).
     */
    public function getContext()
    {
        return $this->context;
    }


    public function toArray(): array
    {
        return [
            'queue_name' => $this->queueName,
            'priority'   => $this->priority,
            'context'    => $this->context,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['queue_name'] ?? '',
            (int) ($data['priority'] ?? 0),
            $data['context'] ?? ''
        );
    }
}