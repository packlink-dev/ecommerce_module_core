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

    /**
     * Converts array to DateTime object.
     *
     * @param array $dateTime DateTime in array format.
     *
     * @return \DateTime | null Date or null.
     */
    public function createDateTimeFromArray($dateTime)
    {
        $value = null;
        if (is_array($dateTime)
            && array_key_exists('date', $dateTime)
            && array_key_exists('timezone_type', $dateTime)
            && array_key_exists('timezone', $dateTime)
        ) {
            try {
                $value = new \DateTime($dateTime['date'], new \DateTimeZone($dateTime['timezone']));
            } catch (\Exception $exception) {
                // if timezone is in offset format, try to convert offset to abbreviation
                if ($dateTime['timezone_type'] === 1) {
                    $offset = (int)str_replace(':', '', $dateTime['timezone']);
                    $timezone = timezone_name_from_abbr('', $offset * 36);
                    if ($timezone) {
                        try {
                            return new \DateTime($dateTime['date'], new \DateTimeZone($timezone));
                        } catch (\Exception $e) {
                            // if this fails proceed to default timezone
                        }
                    }
                }

                try {
                    // try with default timezone
                    $value = new \DateTime($dateTime['date']);
                } catch (\Exception $e) {
                }
            }
        }

        return $value;
    }
}
