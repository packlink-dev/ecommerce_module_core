<?php

namespace Logeecom\Infrastructure;

use Logeecom\Infrastructure\TaskExecution\AsyncProcessStarterService;
use Logeecom\Infrastructure\TaskExecution\Interfaces\AsyncProcessService;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerStatusStorage;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup;
use Logeecom\Infrastructure\TaskExecution\QueueService;
use Logeecom\Infrastructure\TaskExecution\RunnerStatusStorage;
use Logeecom\Infrastructure\TaskExecution\TaskRunner;
use Logeecom\Infrastructure\TaskExecution\TaskRunnerWakeupService;
use Logeecom\Infrastructure\Utility\Events\EventBus;
use Logeecom\Infrastructure\Utility\GuidProvider;
use Logeecom\Infrastructure\Utility\TimeProvider;

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
            QueueService::CLASS_NAME,
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
