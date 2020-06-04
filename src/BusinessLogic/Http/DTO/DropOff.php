<?php

namespace Packlink\BusinessLogic\Http\DTO;

use Logeecom\Infrastructure\Data\DataTransferObject;

/**
 * Class DropOff.
 *
 * @package Packlink\BusinessLogic\Http\DTO
 */
class DropOff extends DataTransferObject
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
            'workingHours' => $this->workingHours,
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

        $entity->id = static::getDataValue($raw, 'id');
        $entity->name = static::getDataValue($raw, 'commerce_name');
        $entity->type = static::getDataValue($raw, 'type');
        $entity->countryCode = static::getDataValue($raw, 'country');
        $entity->state = static::getDataValue($raw, 'state');
        $entity->zip = static::getDataValue($raw, 'zip');
        $entity->city = static::getDataValue($raw, 'city');
        $entity->address = static::getDataValue($raw, 'address');
        $entity->lat = static::getDataValue($raw, 'lat', 0);
        $entity->long = static::getDataValue($raw, 'long', 0);
        $entity->phone = static::getDataValue($raw, 'phone');
        $entity->workingHours =
            !empty($raw['opening_times']['opening_times']) ? $raw['opening_times']['opening_times'] : array();

        return $entity;
    }
}
