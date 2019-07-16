<?php

namespace Packlink\BusinessLogic\Http\DTO\Draft;

use Packlink\BusinessLogic\Http\DTO\BaseDto;

/**
 * Class Address.
 *
 * @package Packlink\BusinessLogic\Http\DTO\Draft
 */
class Address extends BaseDto
{
    /**
     * Name of sender/receiver.
     *
     * @var string
     */
    public $name;
    /**
     * Surname of sender/receiver.
     *
     * @var string
     */
    public $surname;
    /**
     * Company of sender/receiver.
     *
     * @var string
     */
    public $company;
    /**
     * First line of the sender/receiver address.
     *
     * @var string
     */
    public $street1;
    /**
     * Second line of the sender/receiver address.
     *
     * @var string
     */
    public $street2;
    /**
     * The zip code (or postal code).
     *
     * @var string
     */
    public $zipCode;
    /**
     * Address city.
     *
     * @var string
     */
    public $city;
    /**
     * Address country.
     *
     * @var string
     */
    public $country;
    /**
     * The sender's/receiver's phone number.
     *
     * @var string
     */
    public $phone;
    /**
     * The sender's email address.
     *
     * @var string
     */
    public $email;

    /**
     * Transforms DTO to its array format suitable for http client.
     *
     * @return array DTO in array format.
     */
    public function toArray()
    {
        return array(
            'name' => $this->name,
            'surname' => $this->surname,
            'company' => $this->company,
            'street1' => $this->street1,
            'street2' => $this->street2,
            'zip_code' => $this->zipCode,
            'city' => $this->city,
            'country' => $this->country,
            'phone' => $this->phone,
            'email' => $this->email,
        );
    }
}
