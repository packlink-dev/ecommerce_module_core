<?php

namespace Packlink\BusinessLogic\Http\DTO;

class LocationInfo extends BaseDto
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

        $result->id = static::getValue($raw, 'id');
        $result->state = static::getValue($raw, 'state');
        $result->city = static::getValue($raw, 'city');
        $result->zipcode = static::getValue($raw, 'zipcode');
        $result->text = static::getValue($raw, 'text');

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