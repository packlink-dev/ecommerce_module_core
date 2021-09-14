<?php

namespace Packlink\BusinessLogic\DTO;

/**
 * Class ValidationErrors.
 *
 * @package Packlink\BusinessLogic\DTO
 */
class ValidationError extends FrontDto
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Unique class key.
     */
    const CLASS_KEY = 'validation_error';
    /**
     * Error code when required field is missing.
     */
    const ERROR_REQUIRED_FIELD = 'required_field';
    /**
     * Error code when field has an invalid value.
     */
    const ERROR_INVALID_FIELD = 'invalid_field';
    /**
     * Error code.
     *
     * @var string
     */
    public $code;
    /**
     * Field name that contains error.
     *
     * @var string
     */
    public $field;
    /**
     * Error message.
     *
     * @var string
     */
    public $message;
    /**
     * @var array
     */
    protected static $fields = array('code', 'field', 'message');
}
