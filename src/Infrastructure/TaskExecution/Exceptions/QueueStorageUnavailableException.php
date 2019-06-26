<?php

namespace Logeecom\Infrastructure\TaskExecution\Exceptions;

use Logeecom\Infrastructure\Exceptions\BaseException;

/**
 * Class QueueStorageUnavailableException.
 *
 * @package Logeecom\Infrastructure\TaskExecution\Exceptions
 */
class QueueStorageUnavailableException extends BaseException
{
    /**
     * QueueStorageUnavailableException constructor.
     *
     * @param string $message Exception message.
     * @param \Throwable $previous Exception instance that was thrown.
     */
    public function __construct($message = '', $previous = null)
    {
        parent::__construct(trim($message . ' Queue storage failed to save item.'), 0, $previous);
    }
}
