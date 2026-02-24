<?php

namespace Logeecom\Infrastructure\TaskExecution;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Interfaces\AsyncProcessService;
use Logeecom\Infrastructure\TaskExecution\Interfaces\AsyncProcessUrlProviderInterface;
use Logeecom\Infrastructure\TaskExecution\Interfaces\QueueServiceInterface;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerConfigInterface;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerStatusStorage;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup;
use Logeecom\Infrastructure\TaskExecution\Scheduler\ScheduleTickHandler;
use Logeecom\Infrastructure\TaskExecution\Scheduler\TaskRunnerScheduler;
use Logeecom\Infrastructure\TaskExecution\TaskEvents\TickEvent;
use Logeecom\Infrastructure\TaskExecutor\Interfaces\TaskExecutorInterface;
use Logeecom\Infrastructure\TaskExecutor\Interfaces\TaskStatusProviderInterface;
use Logeecom\Infrastructure\Utility\Events\EventBus;
use Logeecom\Infrastructure\Utility\GuidProvider;
use Logeecom\Infrastructure\Utility\TimeProvider;
use Packlink\BusinessLogic\Scheduler\Interfaces\SchedulerInterface;
use Packlink\BusinessLogic\Tasks\Interfaces\TaskMetadataProviderInterface;

/**
 * Registers task execution services only (queue/task runner stack).
 */
class TaskExecutionBootstrap
{
    /**
     * Initializes task execution components.
     */
    public static function init()
    {
        static::initServices();
        static::initEvents();
    }

    /**
     * Initializes task execution services and utilities.
     */
    protected static function initServices()
    {
        ServiceRegister::registerService(
            TaskRunnerConfigInterface::CLASS_NAME,
            function () {
                /** @var Configuration $config */
                $config = ServiceRegister::getService(Configuration::CLASS_NAME);

                /** @var AsyncProcessUrlProviderInterface $urlProvider */
                $urlProvider = ServiceRegister::getService(AsyncProcessUrlProviderInterface::CLASS_NAME);

                return new TaskRunnerConfig($config, $urlProvider);
            }
        );

        ServiceRegister::registerService(
            TimeProvider::CLASS_NAME,
            function () {
                return TimeProvider::getInstance();
            }
        );
        ServiceRegister::registerService(
            GuidProvider::CLASS_NAME,
            function () {
                return GuidProvider::getInstance();
            }
        );
        ServiceRegister::registerService(
            EventBus::CLASS_NAME,
            function () {
                return EventBus::getInstance();
            }
        );
        ServiceRegister::registerService(
            AsyncProcessService::CLASS_NAME,
            function () {
                return AsyncProcessStarterService::getInstance();
            }
        );
        ServiceRegister::registerService(
            QueueServiceInterface::CLASS_NAME,
            function () {
                return new QueueService();
            }
        );
        ServiceRegister::registerService(
            TaskStatusProviderInterface::CLASS_NAME,
            function () {
                /** @var QueueServiceInterface $queueService */
                $queueService = ServiceRegister::getService(QueueServiceInterface::CLASS_NAME);

                /** @var TimeProvider $timeProvider */
                $timeProvider = ServiceRegister::getService(TimeProvider::CLASS_NAME);

                return new QueueTaskStatusProvider($queueService, $timeProvider);
            }
        );
        ServiceRegister::registerService(
            TaskExecutorInterface::CLASS_NAME,
            function () {
                /** @var QueueServiceInterface $queueService */
                $queueService = ServiceRegister::getService(QueueServiceInterface::CLASS_NAME);

                /** @var TaskMetadataProviderInterface $metadataProvider */
                $metadataProvider = ServiceRegister::getService(TaskMetadataProviderInterface::CLASS_NAME);

                /** @var EventBus $eventBus */
                $eventBus = ServiceRegister::getService(EventBus::CLASS_NAME);

                /** @var TimeProvider $timeProvider */
                $timeProvider = ServiceRegister::getService(TimeProvider::CLASS_NAME);

                /** @var SchedulerInterface $scheduler */
                $scheduler = ServiceRegister::getService(SchedulerInterface::CLASS_NAME);

                /** @var TaskRunnerConfigInterface $taskRunnerConfig */
                $taskRunnerConfig = ServiceRegister::getService(TaskRunnerConfigInterface::CLASS_NAME);

                return new HttpTaskExecutor(
                    $queueService,
                    $metadataProvider,
                    $eventBus,
                    $timeProvider,
                    $scheduler,
                    $taskRunnerConfig
                );
            }
        );
        ServiceRegister::registerService(
            SchedulerInterface::CLASS_NAME,
            function () {
                /** @var Configuration $config */
                $config = ServiceRegister::getService(Configuration::CLASS_NAME);

                /** @var TaskRunnerConfigInterface $taskRunnerConfig */
                $taskRunnerConfig = ServiceRegister::getService(TaskRunnerConfigInterface::CLASS_NAME);

                return new TaskRunnerScheduler($config, $taskRunnerConfig);
            }
        );

        ServiceRegister::registerService(
            TaskRunnerWakeup::CLASS_NAME,
            function () {
                return new TaskRunnerWakeupService();
            }
        );
        ServiceRegister::registerService(
            TaskRunner::CLASS_NAME,
            function () {
                /** @var TaskRunnerConfigInterface $taskRunnerConfig */
                $taskRunnerConfig = ServiceRegister::getService(TaskRunnerConfigInterface::CLASS_NAME);

                return new TaskRunner($taskRunnerConfig);
            }
        );
        ServiceRegister::registerService(
            TaskRunnerStatusStorage::CLASS_NAME,
            function () {
                return new RunnerStatusStorage();
            }
        );
    }

    /**
     * Initializes task execution events.
     */
    protected static function initEvents()
    {
        /** @var EventBus $eventBus */
        $eventBus = ServiceRegister::getService(EventBus::CLASS_NAME);

        $eventBus->when(
            TickEvent::CLASS_NAME,
            function () {
                $handler = new ScheduleTickHandler();
                $handler->handle();
            }
        );
    }

}
