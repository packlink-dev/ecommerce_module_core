<?php

namespace Packlink\BusinessLogic\Country;

use Packlink\BusinessLogic\DTO\FrontDto;
use Packlink\BusinessLogic\DTO\ValidationError;

/**
 * Class Country
 *
 * @package Packlink\BusinessLogic\Country
 */
class Country extends FrontDto
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Unique class key.
     */
    const CLASS_KEY = 'country';
    /**
     * Country name in English.
     *
     * @var string
     */
    public $name;
    /**
     * 2-letter country code.
     *
     * @var string
     */
    public $code;
    /**
     * Capital city postal code.
     *
     * @var string
     */
    public $postalCode;
    /**
     * Registration link for the country.
     *
     * @var string
     */
    public $registrationLink;
    /**
     * Fields for this DTO.
     *
     * @var array
     */
    protected static $fields = array(
        'name',
        'code',
        'postal_code',
        'registration_link',
    );

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

        $requiredFields = array(
            'name',
            'code',
            'postal_code',
            'registration_link',
        );

        foreach ($requiredFields as $field) {
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

    /**
     * Transforms raw array data to its DTO.
     *
     * @param array $raw Raw array data.
     *
     * @return static Transformed DTO object.
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public static function fromArray(array $raw)
    {
        /** @var static $instance */
        $instance = parent::fromArray($raw);

        $instance->postalCode = static::getValue($raw, 'postal_code');
        $instance->registrationLink = static::getValue($raw, 'registration_link');

        return $instance;
    }

    /**
     * Transforms DTO to its array format.
     *
     * @return array DTO in array format.
     */
    public function toArray()
    {
        return array_merge(
            parent::toArray(),
            array(
                'postal_code' => $this->postalCode,
                'registration_link' => $this->registrationLink,
            )
        );
    }
}
