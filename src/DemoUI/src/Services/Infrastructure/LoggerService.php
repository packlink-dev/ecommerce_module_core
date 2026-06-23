<?php

namespace Packlink\DemoUI\Services\Infrastructure;

use Logeecom\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use Logeecom\Infrastructure\Logger\LogData;

/**
 * Class LoggerService
 *
 * @package Packlink\PacklinkPro\Services\Infrastructure
 */
class LoggerService implements ShopLoggerAdapter
{
    /**
     * Logs message in the system.
     *
     * @param LogData $data
     */
    public function logMessage(LogData $data)
    {
        $levels = array(0 => 'ERROR', 1 => 'WARNING', 2 => 'INFO', 3 => 'DEBUG');

        // Only surface errors and warnings. INFO/DEBUG are skipped to avoid noise and,
        // importantly, to avoid dumping HTTP debug payloads (which include the API key)
        // into the server log.
        if ((int)$data->getLogLevel() > 1) {
            return;
        }

        $level = isset($levels[$data->getLogLevel()]) ? $levels[$data->getLogLevel()] : $data->getLogLevel();

        error_log(sprintf(
            '[Packlink %s] %s | %s',
            $level,
            $data->getComponent(),
            $data->getMessage()
        ));
    }
}
