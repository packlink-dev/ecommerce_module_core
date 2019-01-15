<?php

namespace Packlink\BusinessLogic\Http\DTO;

class PostalCode extends BaseDto
{
    /**
     * Zipcode.
     *
     * @var string
     */
    public $zipcode;
    /**
     * City name.
     *
     * @var string
     */
    public $city;
    /**
     * State name.
     *
     * @var string
     */
    public $state;
    /**
     * Province name.
     *
     * @var string
     */
    public $province;
    /**
     * Country name.
     *
     * @var string
     */
    public $country;

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

        $instance->zipcode = !empty($raw['zipcode']) ? $raw['zipcode'] : '';
        $instance->city = !empty($raw['city']['name']) ? $raw['city']['name'] : '';
        $instance->state = !empty($raw['state']['name']) ? $raw['state']['name'] : '';
        $instance->province = !empty($raw['province']['name']) ? $raw['province']['name'] : '';
        $instance->country = !empty($raw['country']['name']) ? $raw['country']['name'] : '';

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
            'zipcode' => $this->zipcode,
            'city' => array('name' => $this->city),
            'state' => array('name' => $this->state),
            'province' => array('name' => $this->province),
            'country' => array('name' => $this->country),
        );
    }
}
