<?php

namespace Packlink\BusinessLogic;

/**
 * Base class for all services. Initializes service as a singleton instance.
 *
 * @package Packlink\BusinessLogic
 */
class BaseService
{
    /**
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;

    /**
     * Hidden constructor.
     */
    protected function __construct()
    {
    }

    /**
     * Returns singleton instance of EventBus.
     *
     * @return static Instance of EventBus class.
     */
    public static function getInstance()
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }
}
