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
