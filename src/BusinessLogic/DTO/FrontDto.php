<?php

namespace Packlink\BusinessLogic\DTO;

use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException;

/**
 * Class FrontDto.
 *
 * @package Packlink\BusinessLogic\DTO
 */
abstract class FrontDto extends BaseDto
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Fields for this DTO. Needed for validation and transformation from/to array.
     *
     * @var array
     */
    protected static $fields = array();

    /**
     * Transforms raw array data to its DTO.
     *
     * @param array $raw Raw array data.
     *
     * @return static Transformed DTO object.
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     *  When fields are not registered for DTO class or payload contains unknown fields.
     */
    public static function fromArray(array $raw)
    {
        static::validate($raw);

        $result = new static();
        foreach ($raw as $field => $value) {
            if (property_exists(static::CLASS_NAME, $field)) {
                $result->$field = $value;
            }
        }

        return $result;
    }

    /**
     * Transforms DTO to its array format.
     *
     * @return array DTO in array format.
     */
    public function toArray()
    {
        $result = array();
        foreach (static::$fields as $field) {
            $result[$field] = property_exists(static::CLASS_NAME, $field) ? $this->$field : null;
        }

        return $result;
    }

    /**
     * Validates payload.
     *
     * @param array $payload The payload in key-value format.
     *
     * @return void
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     *  When fields are not registered for DTO class or payload contains unknown fields.
     */
    final protected static function validate(array $payload)
    {
        $validationErrors = static::validatePayload($payload);
        if (!empty($validationErrors)) {
            throw new FrontDtoValidationException($validationErrors);
        }
    }

    /**
     * Generates validation errors for the payload.
     *
     * @param array $payload The payload in key-value format.
     *
     * @return ValidationError[] An array of validation errors, if any.
     */
    protected static function validatePayload(array $payload)
    {
        $validationErrors = array();
        if (empty(static::$fields)) {
            $validationErrors[] = static::getValidationError(
                'fields_not_registered_for_dto_class',
                'fields',
                'Fields are not registered for class ' . static::CLASS_NAME
            );
        }

        return $validationErrors;
    }

    /**
     * Get the instance of the ValidationError class.
     *
     * @param string $code Error code.
     * @param string $field Field name that contains error.
     * @param string $message Error message.
     *
     * @return \Packlink\BusinessLogic\DTO\ValidationError
     * @noinspection PhpDocMissingThrowsInspection
     */
    protected static function getValidationError($code, $field, $message)
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        /** @noinspection PhpUnhandledExceptionInspection */
        return FrontDtoFactory::get(
            ValidationError::CLASS_KEY,
            array('code' => $code, 'field' => $field, 'message' => $message)
        );
    }
}
