<?php

namespace Logeecom\Infrastructure\TaskExecution;

use DateInterval;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\Serializer\Serializer;
use Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use Logeecom\Infrastructure\TaskExecution\Interfaces\QueueServiceInterface;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskExecutorInterface;
use Logeecom\Infrastructure\TaskExecution\TaskEvents\TickEvent;
use Logeecom\Infrastructure\Utility\Events\EventBus;
use Logeecom\Infrastructure\Utility\TimeProvider;
use Packlink\BusinessLogic\Configuration;
use Logeecom\Infrastructure\Scheduler\Models\HourlySchedule;
use Logeecom\Infrastructure\Scheduler\Models\Schedule;
use Logeecom\Infrastructure\Scheduler\ScheduleCheckTask;
use Packlink\BusinessLogic\Tasks\Interfaces\BusinessTask;
use Packlink\BusinessLogic\Tasks\Interfaces\TaskMetadataProviderInterface;
use Packlink\BusinessLogic\Tasks\LegacyTaskAdapter;

class HttpTaskExecutor implements TaskExecutorInterface
{
    /**
     * @var QueueServiceInterface
     */
    private $queueService;

    /**
     * @var TaskMetadataProviderInterface
     */
    private $metadataProvider;

    /**
     * @var EventBus
     */
    private $eventBus;

    /**
     * @var Configuration
     */
    private $configService;
    /**
     * @var TimeProvider
     */
    private $timeProvider;

    /**
     * Registers TickEvent listener to handle schedule ticker.
     */
    public function __construct(
        QueueServiceInterface $queueService,
        TaskMetadataProviderInterface $metadataProvider,
        Configuration $configuration,
        EventBus $eventBus,
        TimeProvider $timeProvider
    ) {
        $this->queueService = $queueService;
        $this->metadataProvider = $metadataProvider;
        $this->eventBus = $eventBus;
        $this->configService = $configuration;
        $this->timeProvider = $timeProvider;

        $this->registerTickEventListener();
    }

    /**
     * Enqueue business task
     * Wraps business task in TaskAdapter (infrastructure requirement),
     * then enqueues to QueueService which automatically triggers HTTP wakeup.
     *
     * @param BusinessTask $businessTask Business task (e.g., SendDraftBusinessTask).
     *
     * @return void
     *
     * @throws QueueStorageUnavailableException
     */
    public function enqueue(BusinessTask $businessTask)
    {
        // Get execution configuration from metadata provider
        $executionConfig = $this->metadataProvider->getExecutionConfig($businessTask);

        $taskAdapter = new TaskAdapter($businessTask);

        $this->queueService->enqueue(
            $executionConfig->getQueueName(),
            $taskAdapter,
            $executionConfig->getContext(),
            $executionConfig->getPriority()
        );
    }

    /**
     * Schedule delayed business task via Schedule entity.
     *
     * Creates HourlySchedule entity which will trigger task execution
     * after specified delay.
     *
     * Uses TaskMetadataProvider to get HTTP-specific configuration.
     *
     * @param BusinessTask $businessTask Business task.
     * @param int $delaySeconds Delay in seconds.
     *
     * @return void
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function scheduleDelayed(BusinessTask $businessTask, int $delaySeconds)
    {
        // Get execution configuration from metadata provider
        $executionConfig = $this->metadataProvider->getExecutionConfig($businessTask);

        // Wrap business task in adapter
        $taskAdapter = new TaskAdapter($businessTask);

        // Calculate execution time
        $delayMinutes = ceil($delaySeconds / 60);
        /** @noinspection PhpUnhandledExceptionInspection */
        $timestamp = $this->timeProvider->getCurrentLocalTime()
            ->add(new DateInterval('PT' . $delayMinutes . 'M'))
            ->getTimestamp();

        // Create delayed schedule
        $schedule = new HourlySchedule(
            $taskAdapter,
            $executionConfig->getQueueName(),
            $executionConfig->getContext()
        );

        $schedule->setMonth((int)date('m', $timestamp));
        $schedule->setDay((int)date('d', $timestamp));
        $schedule->setHour((int)date('H', $timestamp));
        $schedule->setMinute((int)date('i', $timestamp));
        $schedule->setRecurring(false);
        $schedule->setNextSchedule();

        RepositoryRegistry::getRepository(Schedule::CLASS_NAME)->save($schedule);
    }

    /**
     * Register TickEvent listener.
     *
     * Subscribes to TickEvent from ScheduleTickHandler to check if ScheduleCheckTask
     * needs to be enqueued.
     *
     * @return void
     */
    private function registerTickEventListener()
    {
        // Subscribe to TickEvent
        $this->eventBus->when(TickEvent::CLASS_NAME, array($this, 'handleTickEvent'));
    }

    /**
     * Handle TickEvent from ScheduleTickHandler.
     *
     * Checks QueueService to see if ScheduleCheckTask is already enqueued or running.
     * If not (or if it's old), enqueues a new ScheduleCheckTask.
     *
     * @return void
     */
    public function handleTickEvent()
    {
        $task = $this->queueService->findLatestByType('ScheduleCheckTask');
        $threshold = $this->configService->getSchedulerTimeThreshold();

        $this->enqueueCheckTaskIfNeeded($task, $threshold);
    }

    /**
     * Enqueue ScheduleCheckTask if needed.
     *
     * Checks if ScheduleCheckTask should be enqueued based on its status and age.
     *
     * @param QueueItem|null $task Latest ScheduleCheckTask from queue.
     * @param int $threshold Minimum age (in seconds) of last ScheduleCheckTask.
     *
     * @return void
     */
    private function enqueueCheckTaskIfNeeded($task, int $threshold)
    {
        // Don't enqueue if task is already queued or in progress
        if ($task && in_array($task->getStatus(), array(QueueItem::QUEUED, QueueItem::IN_PROGRESS), true)) {
            return;
        }

        // Enqueue if no task exists or task is old enough
        if ($task === null || $task->getQueueTimestamp() + $threshold < time()) {
            $this->enqueueScheduleCheckTask();
        }
    }

    /**
     * Enqueue ScheduleCheckTask.
     *
     * Wraps ScheduleCheckTask in LegacyTaskAdapter and enqueues via TaskExecutor.
     *
     * @return void
     */
    private function enqueueScheduleCheckTask()
    {
        $task = new ScheduleCheckTask();

        try {
            // Wrap Infrastructure Task in LegacyTaskAdapter
            $businessTask = new LegacyTaskAdapter($task);

            // Enqueue via TaskExecutor (self - uses HTTP wakeup)
            $this->enqueue($businessTask);

        } catch (QueueStorageUnavailableException $ex) {
            Logger::logDebug(
                'Failed to enqueue ScheduleCheckTask',
                'Core',
                array(
                    'ExceptionMessage' => $ex->getMessage(),
                    'ExceptionTrace' => $ex->getTraceAsString(),
                    'TaskData' => Serializer::serialize($task),
                )
            );
        } catch (\Exception $ex) {
            Logger::logDebug(
                'Failed to enqueue ScheduleCheckTask via TaskExecutor',
                'Core',
                array(
                    'ExceptionMessage' => $ex->getMessage(),
                    'ExceptionTrace' => $ex->getTraceAsString(),
                )
            );
        }
    }
}
