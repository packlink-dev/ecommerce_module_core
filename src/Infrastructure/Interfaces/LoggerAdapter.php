<?php

namespace Logeecom\Infrastructure\Interfaces;

use Logeecom\Infrastructure\Logger\LogData;

/**
 * Interface LoggerAdapter.
 *
 * @package Logeecom\Infrastructure\Interfaces
 */
interface LoggerAdapter
{
    /**
     * Log message in system
     *
     * @param LogData $data
     */
    public function logMessage(LogData $data);
}
