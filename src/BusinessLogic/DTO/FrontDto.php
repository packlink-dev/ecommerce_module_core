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
     */
    public static function fromArray(array $raw)
    {
        static::validate($raw);

        $result = new static();
        foreach (static::$fields as $field) {
            $result->$field = static::getValue($raw, $field);
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
            $result[$field] = $this->$field;
        }

        return $result;
    }

    /**
     * Validates payload.
     *
     * @param array $payload The payload in key-value format.
     *
     * @return void
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException When payload is not valid for this
     *     DTO.
     */
    protected static function validate(array $payload)
    {
        $validationErrors = array();
        if (empty(static::$fields)) {
            $validationErrors[] = ValidationError::fromArray(
                array('code' => 'fields_not_registered', 'field' => 'fields', 'message' => 'Fields are not registered.')
            );
        }

        foreach (static::$fields as $field) {
            if (!array_key_exists($field, $payload)) {
                $validationErrors[] = ValidationError::fromArray(
                    array('code' => 'missing_field', 'field' => $field, 'message' => 'Missing field.')
                );
            }
        }

        if (!empty($validationErrors)) {
            throw new FrontDtoValidationException($validationErrors);
        }
    }
}
