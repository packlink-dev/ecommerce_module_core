<?php

namespace Packlink\BusinessLogic\Http\DTO;

/**
 * Class Warehouse
 * @package Packlink\BusinessLogic\Http\DTO
 */
class Warehouse extends BaseDto
{
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
     * Created date of the warehouse.
     *
     * @var \DateTime
     */
    public $createdAt;
    /**
     * Represent if it's the default warehouse.
     *
     * @var bool
     */
    public $default;

    /**
     * Transforms raw array data to its DTO.
     *
     * @param array $raw Raw array data.
     *
     * @return static Transformed DTO object.
     */
    public static function fromArray(array $raw)
    {
        $instance = new static();
        $instance->id = static::getValue($raw, 'id');
        $instance->city = static::getValue($raw, 'city');
        $instance->name = static::getValue($raw, 'name');
        $instance->surname = static::getValue($raw, 'surname');
        $instance->phone = static::getValue($raw, 'phone');
        $instance->country = static::getValue($raw, 'country');
        $instance->company = static::getValue($raw, 'company');
        $instance->email = static::getValue($raw, 'email');
        $instance->alias = static::getValue($raw, 'alias');
        $instance->postalCode = static::getValue($raw, 'postal_code');
        $instance->address = static::getValue($raw, 'address');
        $instance->createdAt = static::getValue($raw, 'created_at');
        $instance->createdAt = $instance->createdAt ? \DateTime::createFromFormat('Y-m-d H:i:s', $instance->createdAt)
            : null;
        $instance->default = static::getValue($raw, 'default_selection');

        return $instance;
    }

    /**
     * Transforms DTO to its array format suitable for http client.
     *
     * @return array DTO in array format.
     */
    public function toArray()
    {
        return array(
            'id' => $this->id,
            'city' => $this->city,
            'name' => $this->name,
            'surname' => $this->surname,
            'phone' => $this->phone,
            'country' => $this->country,
            'company' => $this->company,
            'email' => $this->email,
            'alias' => $this->alias,
            'postal_code' => $this->postalCode,
            'address' => $this->address,
            'created_at' => $this->createdAt ? $this->createdAt->format('Y-m-d H:i:s') : null,
            'default_selection' => $this->default,
        );
    }
}
