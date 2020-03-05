<?php

namespace Packlink\BusinessLogic\Warehouse;

use Packlink\BusinessLogic\DTO\FrontDto;
use Packlink\BusinessLogic\DTO\ValidationError;

/**
 * Class Warehouse.
 *
 * @package Packlink\BusinessLogic\Warehouse
 */
class Warehouse extends FrontDto
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Unique class key.
     */
    const CLASS_KEY = 'warehouse';
    /**
     * Id of the warehouse.
     *
     * @var string
     */
    public $id;
    /**
     * City of the warehouse.
     *
     * @var string
     */
    public $city;
    /**
     * Name of warehouse.
     *
     * @var string
     */
    public $name;
    /**
     * Surname of warehouse.
     *
     * @var string
     */
    public $surname;
    /**
     * Phone of warehouse.
     *
     * @var string
     */
    public $phone;
    /**
     * Country of warehouse.
     *
     * @var string
     */
    public $country;
    /**
     * Company of warehouse.
     *
     * @var string
     */
    public $company;
    /**
     * Email of warehouse.
     *
     * @var string
     */
    public $email;
    /**
     * Alias of warehouse.
     *
     * @var string
     */
    public $alias;
    /**
     * Postal code of warehouse.
     *
     * @var string
     */
    public $postalCode;
    /**
     * Address code of warehouse.
     *
     * @var string
     */
    public $address;
    /**
     * Represent if it's the default warehouse.
     *
     * @var bool
     */
    public $default;
    /**
     * Fields for this DTO.
     *
     * @var array
     */
    protected static $fields = array(
        'id',
        'city',
        'name',
        'surname',
        'phone',
        'country',
        'company',
        'email',
        'alias',
        'address',
    );
    /**
     * Required fields for DTO to be valid.
     *
     * @var array
     */
    protected static $requiredFields = array(
        'alias',
        'name',
        'surname',
        'country',
        'postal_code',
        'address',
        'phone',
        'email',
    );

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
        $instance->default = static::getValue($raw, 'default_selection', false);
        $instance->postalCode = static::getValue($raw, 'postal_code');

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
            array('postal_code' => $this->postalCode, 'default_selection' => $this->default)
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

        if (!empty($payload['email']) && filter_var($payload['email'], FILTER_VALIDATE_EMAIL) === false) {
            $validationErrors[] = static::getValidationError(
                ValidationError::ERROR_INVALID_FIELD,
                'email',
                'Field must be a valid email.'
            );
        }

        if (!empty($payload['phone'])) {
            $regex = '/^(\+|\/|\.|-|\(|\)|\d)+$/m';
            $phoneError = !preg_match($regex, $payload['phone']);

            $digits = '/\d/m';
            $match = preg_match_all($digits, $payload['phone'], $matches);
            $phoneError |= $match === false || $match < 3;

            if ($phoneError) {
                $validationErrors[] = static::getValidationError(
                    ValidationError::ERROR_INVALID_FIELD,
                    'phone',
                    'Field must be a valid phone number.'
                );
            }
        }
    }
}
