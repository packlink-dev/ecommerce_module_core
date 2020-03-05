<?php

namespace Logeecom\Infrastructure;

/**
 * Base class for all singleton implementations.
 * Every class that extends this class MUST have its own protected static field $instance!
 *
 * @package Logeecom\Infrastructure
 */
abstract class Singleton
{
    /**
     * Hidden constructor.
     */
    protected function __construct()
    {
    }

    /**
     * Returns singleton instance of callee class.
     *
     * @return static Instance of callee class.
     */
    public static function getInstance()
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        if (!(static::$instance instanceof static)) {
            throw new \RuntimeException('Wrong static instance of a singleton class.');
        }

        return static::$instance;
    }

    /**
     * Resets singleton instance. Required for proper tests.
     */
    public static function resetInstance()
    {
        static::$instance = null;
    }
}
