<?php

namespace Logeecom\Infrastructure\Exceptions;

/**
 * Class ServiceAlreadyRegisteredException.
 *
 * @package Logeecom\Infrastructure\Exceptions
 */
class ServiceAlreadyRegisteredException extends BaseException
{
    /**
     * ServiceAlreadyRegisteredException constructor.
     *
     * @param string $type Type of service. Should be fully qualified class name.
     */
    public function __construct($type)
    {
        parent::__construct("Service of type \"$type\" is already registered.");
    }
}
