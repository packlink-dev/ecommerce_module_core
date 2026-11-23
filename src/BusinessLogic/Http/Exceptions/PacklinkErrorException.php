<?php

namespace Packlink\BusinessLogic\Http\Exceptions;

use Logeecom\Infrastructure\Http\Exceptions\HttpRequestException;

/**
 * Class PacklinkErrorException. An unsuccessful Packlink API response that keeps the HTTP status as the
 * exception code and the Packlink error code from "messages[].error_code", so callers can tell apart
 * outcomes that share an HTTP status.
 *
 * @package Packlink\BusinessLogic\Http\Exceptions
 */
class PacklinkErrorException extends HttpRequestException
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Packlink error code, or an empty string when the response carried none.
     *
     * @var string
     */
    private $errorCode;

    /**
     * PacklinkErrorException constructor.
     *
     * @param string $message Diagnostic message from the response.
     * @param int $httpStatus HTTP status of the response.
     * @param string $errorCode Packlink error code, or an empty string.
     */
    public function __construct($message, $httpStatus, $errorCode)
    {
        parent::__construct($message, $httpStatus);

        $this->errorCode = (string)$errorCode;
    }

    /**
     * Returns the Packlink error code, or an empty string when the response carried none.
     *
     * @return string
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }
}
