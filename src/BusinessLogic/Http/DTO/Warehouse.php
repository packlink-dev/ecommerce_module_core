<?php

namespace Packlink\BusinessLogic\Http\DTO;

use Packlink\BusinessLogic\DTO\FrontDto;

/**
 * Class Warehouse.
 *
 * @package Packlink\BusinessLogic\Http\DTO
 */
class Warehouse extends FrontDto
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
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
        'postal_code',
        'address',
        'default_selection',
        'created_at',
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
}
