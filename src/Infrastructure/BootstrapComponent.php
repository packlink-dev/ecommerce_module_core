<?php

namespace Logeecom\Infrastructure;

use Logeecom\Infrastructure\TaskExecution\AsyncProcessStarterService;
use Logeecom\Infrastructure\TaskExecution\HttpTaskExecutor;
use Logeecom\Infrastructure\TaskExecution\Interfaces\AsyncProcessService;
use Logeecom\Infrastructure\TaskExecution\Interfaces\QueueServiceInterface;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskExecutorInterface;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerStatusStorage;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskStatusProviderInterface;
use Logeecom\Infrastructure\TaskExecution\QueueService;
use Logeecom\Infrastructure\TaskExecution\QueueTaskStatusProvider;
use Logeecom\Infrastructure\TaskExecution\RunnerStatusStorage;
use Logeecom\Infrastructure\TaskExecution\TaskRunner;
use Logeecom\Infrastructure\TaskExecution\TaskRunnerWakeupService;
use Logeecom\Infrastructure\Utility\Events\EventBus;
use Logeecom\Infrastructure\Utility\GuidProvider;
use Logeecom\Infrastructure\Utility\TimeProvider;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Tasks\Interfaces\TaskMetadataProviderInterface;

/**
 * Class BootstrapComponent.
 *
 * @package Logeecom\Infrastructure
 */
class BootstrapComponent
{
    /**
     * Initializes infrastructure components.
     */
    public static function init()
    {
        static::initServices();
        static::initRepositories();
        static::initEvents();
    }

    /**
     * Initializes services and utilities.
     */
    protected static function initServices()
    {
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
            TaskRunnerWakeup::CLASS_NAME,
            function () {
                return new TaskRunnerWakeupService();
            }
        );
        ServiceRegister::registerService(
            TaskRunner::CLASS_NAME,
            function () {
                return new TaskRunner();
            }
        );
        ServiceRegister::registerService(
            TaskRunnerStatusStorage::CLASS_NAME,
            function () {
                return new RunnerStatusStorage();
            }
        );


        ServiceRegister::registerService(
            TaskStatusProviderInterface::CLASS_NAME,
            function () {
                /** @var QueueServiceInterface $queueService */
                $queueService = ServiceRegister::getService(QueueServiceInterface::CLASS_NAME);

                return new QueueTaskStatusProvider($queueService);
            }
        );

        ServiceRegister::registerService(
            TaskExecutorInterface::CLASS_NAME,
            function () {
                /** @var QueueServiceInterface $queueService */
                $queueService = ServiceRegister::getService(QueueServiceInterface::CLASS_NAME);

                /** @var TaskMetadataProviderInterface $metadataProvider */
                $metadataProvider = ServiceRegister::getService(TaskMetadataProviderInterface::CLASS_NAME);

                /** @var Configuration $config */
                $config = ServiceRegister::getService(Configuration::CLASS_NAME);

                /** @var EventBus $eventBus */
                $eventBus = ServiceRegister::getService(EventBus::CLASS_NAME);

                return new HttpTaskExecutor($queueService, $metadataProvider, $config, $eventBus);
            }
        );
    }

    /**
     * Initializes repositories.
     */
    protected static function initRepositories()
    {
    }

    /**
     * Initializes events.
     */
    protected static function initEvents()
    {
    }
}
