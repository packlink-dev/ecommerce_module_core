<?php

namespace Packlink\BusinessLogic\Http\DTO;

use Packlink\BusinessLogic\DTO\FrontDto;
use Packlink\BusinessLogic\DTO\FrontDtoFactory;
use Packlink\BusinessLogic\DTO\ValidationError;

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

        $options = array('options' => array('min_range' => 0));
        foreach (array('width', 'length', 'height') as $field) {
            if (!empty($payload[$field]) && filter_var($payload[$field], FILTER_VALIDATE_INT, $options) === false) {
                $validationErrors[] = static::getValidationError(
                    ValidationError::ERROR_INVALID_FIELD,
                    $field,
                    ucfirst($field) . ' must be a positive integer.'
                );
            }
        }

        if (!empty($payload['weight'])
            && (filter_var($payload['weight'], FILTER_VALIDATE_FLOAT) === false || $payload['weight'] <= 0)
        ) {
            $validationErrors[] = static::getValidationError(
                ValidationError::ERROR_INVALID_FIELD,
                'weight',
                'Weight must be a positive decimal number.'
            );
        }
    }
}
