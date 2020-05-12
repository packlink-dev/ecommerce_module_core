<?php

namespace Packlink\BusinessLogic\DTO\Exceptions;

use Logeecom\Infrastructure\Exceptions\BaseException;
use Packlink\BusinessLogic\DTO\ValidationError;

/**
 * Class FrontDtoValidationException.
 *
 * @package Packlink\BusinessLogic\DTO\Exceptions
 */
class FrontDtoValidationException extends BaseException
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Validation errors.
     *
     * @var ValidationError[]
     */
    protected $validationErrors;

    /**
     * FrontDtoValidationException constructor.
     *
     * @param ValidationError[] $validationErrors Validation errors.
     * @param string $message [optional] The Exception message to throw.
     * @param int $code [optional] The Exception code.
     * @param object $previous [optional] The previous throwable used for the exception chaining.
     */
    public function __construct(array $validationErrors, $message = '', $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->validationErrors = $validationErrors;
    }

    /**
     * Gets ValidationErrors.
     *
     * @return ValidationError[] ValidationErrors.
     */
    public function getValidationErrors()
    {
        return $this->validationErrors;
    }
}
