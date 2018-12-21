<?php

namespace Logeecom\Infrastructure\Utility;

/**
 * Class TimeProvider.
 *
 * @package Logeecom\Infrastructure\Utility
 */
class TimeProvider
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Singleton instance.
     *
     * @var TimeProvider
     */
    protected static $instance;

    /**
     * TimeProvider constructor
     */
    private function __construct()
    {
    }

    /**
     * Returns singleton instance of TimeProvider.
     *
     * @return TimeProvider An instance.
     */
    public static function getInstance()
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * @noinspection PhpDocMissingThrowsInspection
     *
     * Gets current time in default server timezone.
     *
     * @return \DateTime Current time as @see \DateTime object.
     */
    public function getCurrentLocalTime()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return new \DateTime();
    }

    /**
     * @noinspection PhpDocMissingThrowsInspection
     *
     * Returns @see \DateTime object from timestamp.
     *
     * @param int $timestamp Timestamp in seconds.
     *
     * @return \DateTime Object from timestamp.
     */
    public function getDateTime($timestamp)
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return new \DateTime("@{$timestamp}");
    }

    /**
     * Returns current timestamp in milliseconds
     *
     * @return int Current time in milliseconds.
     */
    public function getMillisecondsTimestamp()
    {
        return (int)round(microtime(true) * 1000);
    }

    /**
     * Delays execution for sleep time seconds.
     *
     * @param int $sleepTime Sleep time in seconds.
     */
    public function sleep($sleepTime)
    {
        sleep($sleepTime);
    }
}
