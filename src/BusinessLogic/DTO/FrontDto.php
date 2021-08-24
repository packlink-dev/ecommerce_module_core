<?php

namespace Packlink\BusinessLogic\DTO;

use Logeecom\Infrastructure\Data\DataTransferObject;
use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException;
use Packlink\BusinessLogic\Language\Translator;

/**
 * Class FrontDto.
 *
 * @package Packlink\BusinessLogic\DTO
 */
abstract class FrontDto extends DataTransferObject
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
     * Required fields for DTO to be valid.
     *
     * @var array
     */
    protected static $requiredFields = array();

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
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     *  When fields are not registered for DTO class or payload contains unknown fields.
     */
    protected static function validate(array $payload)
    {
        $validationErrors = array();

        static::validateDefinition($validationErrors);
        static::validateRequiredFields($payload, $validationErrors);
        static::doValidate($payload, $validationErrors);

        if (!empty($validationErrors)) {
            throw new FrontDtoValidationException($validationErrors);
        }
    }

    /**
     * Validates whether a DTO has a definition of its fields.
     *
     * @param ValidationError[] $validationErrors The array of errors to populate.
     */
    protected static function validateDefinition(array &$validationErrors)
    {
        if (empty(static::$fields)) {
            $validationErrors[] = static::getValidationError(
                'fields_not_registered_for_dto_class',
                'fields',
                'Fields are not registered for class ' . static::CLASS_NAME
            );
        }
    }

    /**
     * Checks the payload for mandatory fields.
     *
     * @param array $payload The payload in key-value format.
     * @param ValidationError[] $validationErrors The array of errors to populate.
     */
    protected static function validateRequiredFields(array $payload, array &$validationErrors)
    {
        foreach (static::$requiredFields as $field) {
            self::validateRequiredField($payload, $field, $validationErrors);
        }
    }

    /**
     * Validates a required field.
     *
     * @param array $payload The payload.
     * @param string $field The field code.
     * @param ValidationError[] $validationErrors The array of errors to populate.
     *
     * @return bool Returns the result of validation.
     */
    protected static function validateRequiredField(array $payload, $field, array &$validationErrors)
    {
        if (!static::isFieldSet($payload, $field)) {
            static::setRequiredFieldError($field, $validationErrors);

            return false;
        }

        return true;
    }

    /**
     * Checks if a required field is set in payload.
     *
     * @param array $payload The input payload.
     * @param string $field Field code.
     *
     * @return bool TRUE if field is set; otherwise, false;
     */
    protected static function isFieldSet(array $payload, $field)
    {
        return isset($payload[$field]);
    }

    /**
     * Generates validation errors for the payload.
     *
     * @param array $payload The payload in key-value format.
     * @param ValidationError[] $validationErrors The array of errors to populate.
     */
    protected static function doValidate(array $payload, array &$validationErrors)
    {
    }

    /**
     * Sets the required field validation error.
     *
     * @param string $code The field code.
     * @param ValidationError[] $validationErrors The array of errors to populate.
     */
    protected static function setRequiredFieldError($code, array &$validationErrors)
    {
        $validationErrors[] = static::getValidationError(
            ValidationError::ERROR_REQUIRED_FIELD,
            $code,
            Translator::translate('validation.requiredField')
        );
    }

    /**
     * Sets the invalid field validation error.
     *
     * @param string $code The field code.
     * @param ValidationError[] $validationErrors The array of errors to populate.
     * @param string $message Optional field message
     */
    protected static function setInvalidFieldError($code, array &$validationErrors, $message = '')
    {
        $validationErrors[] = static::getValidationError(
            ValidationError::ERROR_INVALID_FIELD,
            $code,
            $message ?: Translator::translate('validation.invalidField')
        );
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
