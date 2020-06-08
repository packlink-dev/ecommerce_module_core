<?php

namespace Packlink\BusinessLogic\Http\DTO;

use Logeecom\Infrastructure\Data\DataTransferObject;

/**
 * Class LocationInfo.
 *
 * @package Packlink\BusinessLogic\Http\DTO
 */
class LocationInfo extends DataTransferObject
{
    /**
     * Id.
     *
     * @var string
     */
    public $id;
    /**
     * State.
     *
     * @var string
     */
    public $state;
    /**
     * City.
     *
     * @var string
     */
    public $city;
    /**
     * Zipcode.
     *
     * @var string
     */
    public $zipcode;
    /**
     * Text.
     *
     * @var string
     */
    public $text;

    /**
     * Creates LocationInfo object from array.
     *
     * @param array $raw
     *
     * @return LocationInfo
     */
    public static function fromArray(array $raw)
    {
        $result = new self();

        $result->id = static::getDataValue($raw, 'id');
        $result->state = static::getDataValue($raw, 'state');
        $result->city = static::getDataValue($raw, 'city');
        $result->zipcode = static::getDataValue($raw, 'zipcode');
        $result->text = static::getDataValue($raw, 'text');

        return $result;
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
            'state' => $this->state,
            'city' => $this->city,
            'zipcode' => $this->zipcode,
            'text' => $this->text,
        );
    }
}