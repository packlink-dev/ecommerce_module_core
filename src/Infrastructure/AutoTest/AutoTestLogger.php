<?php

namespace Logeecom\Infrastructure\AutoTest;

use Logeecom\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use Logeecom\Infrastructure\Logger\LogData;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\Singleton;

/**
 * Class AutoTestLogger.
 *
 * @package Logeecom\Infrastructure\AutoConfiguration
 */
class AutoTestLogger extends Singleton implements ShopLoggerAdapter
{
    /**
     * Logs a message in system.
     *
     * @param LogData $data Data to log.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function logMessage(LogData $data)
    {
        $repo = RepositoryRegistry::getRepository(LogData::CLASS_NAME);
        $repo->save($data);
    }

    /**
     * Gets all log entities.
     *
     * @return LogData[] An array of the LogData entities, if any.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function getLogs()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return RepositoryRegistry::getRepository(LogData::CLASS_NAME)->select();
    }

    /**
     * Transforms logs to the plain array.
     *
     * @return array An array of logs.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function getLogsArray()
    {
        $result = array();
        foreach ($this->getLogs() as $log) {
            $result[] = $log->toArray();
        }

        return $result;
    }
}
