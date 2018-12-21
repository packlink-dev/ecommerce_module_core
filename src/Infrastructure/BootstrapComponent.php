<?php

namespace Logeecom\Infrastructure;

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
     *
     * @throws \Logeecom\Infrastructure\Exceptions\ServiceAlreadyRegisteredException
     */
    public static function init()
    {
        static::initServices();
        static::initEvents();
    }

    /**
     * Initializes infrastructure services and utilities.
     *
     * @throws \Logeecom\Infrastructure\Exceptions\ServiceAlreadyRegisteredException
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
    }

    /**
     * Initializes infrastructure events.
     */
    protected static function initEvents()
    {
    }
}
