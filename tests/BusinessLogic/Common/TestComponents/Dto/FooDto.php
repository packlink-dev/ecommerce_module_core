<?php

namespace Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto;

use Packlink\BusinessLogic\DTO\FrontDto;
use Packlink\BusinessLogic\DTO\ValidationError;

/**
 * Class FooDto.
 *
 * @package Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto
 */
class FooDto extends FrontDto
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Unique class key.
     */
    const CLASS_KEY = 'foo';
    /**
     * @var string
     */
    public $foo;
    /**
     * @var string
     */
    public $bar;
    /**
     * @var array
     */
    protected static $fields = array('foo', 'bar');

    /**
     * Generates validation errors for the payload.
     *
     * @param array $payload The payload in key-value format.
     *
     * @return ValidationError[] An array of validation errors, if any.
     */
    protected static function validatePayload(array $payload)
    {
        $validationErrors = parent::validatePayload($payload);
        foreach (self::$fields as $field) {
            if (empty($payload[$field])) {
                $validationErrors[] = static::getValidationError(
                    ValidationError::ERROR_REQUIRED_FIELD,
                    $field,
                    'Field is required.'
                );
            }
        }

        return $validationErrors;
    }
}
