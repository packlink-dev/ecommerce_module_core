<?php

namespace Packlink\BusinessLogic\Http\DTO;

/**
 * Class DropOff.
 *
 * @package Packlink\BusinessLogic\Http\DTO
 */
class DropOff extends BaseDto
{
    /**
     * Unique identifier of drop-off point.
     *
     * @var string
     */
    public $id;
    /**
     * Name of the service.
     *
     * @var string
     */
    public $name;
    /**
     * Type of the service.
     *
     * @var string
     */
    public $type;
    /**
     * Two letter country code.
     *
     * @var string
     */
    public $countryCode;
    /**
     * Service's state.
     *
     * @var string
     */
    public $state;
    /**
     * Services zip code.
     *
     * @var string
     */
    public $zip;
    /**
     * City name.
     *
     * @var string
     */
    public $city;
    /**
     * Street address of the service.
     *
     * @var string
     */
    public $address;
    /**
     * Latitude.
     *
     * @var float
     */
    public $lat;
    /**
     * Longitude
     *
     * @var float
     */
    public $long;
    /**
     * Full phone number of the service.
     *
     * @var string
     */
    public $phone;
    /**
     * Working hours of the service.
     *
     * @example ['monday' => '11:00-14:00, 16:00-19:00', "wednesday" => "11:00-14:00, 16:00-19:00", ...].
     *
     * @var array
     */
    public $workingHours;


    /**
     * Transforms DTO to its array format suitable for http client.
     *
     * @return array DTO in array format.
     */
    public function toArray()
    {
        return array(
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'countryCode' => $this->countryCode,
            'state' => $this->state,
            'zip' => $this->zip,
            'city' => $this->city,
            'address' => $this->address,
            'lat' => $this->lat,
            'long' => $this->long,
            'phone' => $this->phone,
            'workingHours' => $this->workingHours
        );
    }

    /**
     * Creates DropOff object from array.
     *
     * @param array $raw
     *
     * @return DropOff
     */
    public static function fromArray(array $raw)
    {
        $entity = new self();

        $entity->id = static::getValue($raw, 'id');
        $entity->name = static::getValue($raw, 'commerce_name');
        $entity->type = static::getValue($raw, 'type');
        $entity->countryCode = static::getValue($raw, 'country');
        $entity->state = static::getValue($raw, 'state');
        $entity->zip = static::getValue($raw, 'zip');
        $entity->city = static::getValue($raw, 'city');
        $entity->address = static::getValue($raw, 'address');
        $entity->lat = static::getValue($raw, 'lat', 0);
        $entity->long = static::getValue($raw, 'long', 0);
        $entity->phone = static::getValue($raw, 'phone');
        $entity->workingHours =
            !empty($raw['opening_times']['opening_times']) ? $raw['opening_times']['opening_times'] : array();

        return $entity;
    }
}
