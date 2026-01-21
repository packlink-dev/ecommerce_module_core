<?php

namespace Logeecom\Infrastructure\TaskExecution;

use Logeecom\Infrastructure\TaskExecution\Interfaces\QueueServiceInterface;

class QueueTaskStatusProvider implements Interfaces\TaskStatusProviderInterface
{
    /**
     * @var QueueServiceInterface
     */
    private $queueService;


    public function __construct(QueueServiceInterface $queueService)
    {
        $this->queueService = $queueService;
    }

    /**
     * @inheritDoc
     */
    public function getLatestStatus(string $type, string $context = ''): array
    {
        $item = $this->queueService->findLatestByType($type, $context);

        if($item === null){
            return [];
        }

        return [
            'status' => $item->getStatus(),
            'message' => $item->getFailureDescription(),
        ];
    }
}