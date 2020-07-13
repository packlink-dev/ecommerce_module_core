<?php

namespace Packlink\BusinessLogic\Http\DTO;

use Packlink\BusinessLogic\DTO\FrontDto;
use Packlink\BusinessLogic\DTO\FrontDtoFactory;
use Packlink\BusinessLogic\DTO\ValidationError;
use Packlink\BusinessLogic\Language\Translator;

/**
 * Class ParcelInfo.
 *
 * @package Packlink\BusinessLogic\Http\DTO
 */
class ParcelInfo extends FrontDto
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Unique class key.
     */
    const CLASS_KEY = 'parcel';
    /**
     * Weight of the parcel.
     *
     * @var float
     */
    public $weight;
    /**
     * Length of the parcel.
     *
     * @var float
     */
    public $length;
    /**
     * Height of the parcel.
     *
     * @var float
     */
    public $height;
    /**
     * Width of the parcel.
     *
     * @var float
     */
    public $width;
    /**
     * Represent if it's the default parcel.
     *
     * @var bool
     */
    public $default;
    /**
     * Fields for this DTO. Needed for validation and transformation from/to array.
     *
     * @var array
     */
    protected static $fields = array(
        'weight',
        'width',
        'length',
        'height',
        'default',
    );
    /**
     * Required fields for DTO to be valid.
     *
     * @var array
     */
    protected static $requiredFields = array(
        'weight',
        'width',
        'length',
        'height',
    );

    /**
     * Gets default parcel details.
     *
     * @return static Default parcel.
     * @noinspection PhpDocMissingThrowsInspection
     */
    public static function defaultParcel()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        /** @noinspection PhpUnhandledExceptionInspection */
        return FrontDtoFactory::get(
            static::CLASS_KEY,
            array(
                'weight' => 1,
                'width' => 10,
                'height' => 10,
                'length' => 10,
                'default' => true,
            )
        );
    }

    /**
     * Generates validation errors for the payload.
     *
     * @param array $payload The payload in key-value format.
     * @param ValidationError[] $validationErrors The array of errors to populate.
     */
    protected static function doValidate(array $payload, array &$validationErrors)
    {
        parent::doValidate($payload, $validationErrors);

        foreach (array('width', 'length', 'height') as $field) {
            static::validateNumber($payload, $field, $validationErrors, FILTER_VALIDATE_INT);
        }

        static::validateNumber($payload, 'weight', $validationErrors, FILTER_VALIDATE_FLOAT);
    }

    /**
     * Validates if the given value is a number.
     *
     * @param array $payload
     * @param string $field The field key.
     * @param ValidationError[] $validationErrors The list of validation errors to alter.
     * @param int $filter Validation filter
     */
    private static function validateNumber(array $payload, $field, &$validationErrors, $filter)
    {
        if (!isset($payload[$field])) {
            // required field validation already happened
            return;
        }

        $value = filter_var($payload[$field], $filter);
        if ($value === false) {
            $validationErrors[] = static::getValidationError(
                ValidationError::ERROR_INVALID_FIELD,
                $field,
                Translator::translate('validation.integer')
            );
        } elseif ($value <= 0) {
            $validationErrors[] = static::getValidationError(
                ValidationError::ERROR_INVALID_FIELD,
                $field,
                Translator::translate('validation.greaterThanZero')
            );
        }
    }
}
