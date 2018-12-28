<?php

namespace Packlink\BusinessLogic\Http\DTO;

/**
 * This class holds search parameters that are used when searching for services
 * for specific source and destination location information and specific parcel.
 *
 * @package Packlink\BusinessLogic\Http\DTO
 */
class ShippingServiceSearch extends BaseDto
{
    /**
     * Service ID. Optional.
     *
     * @var int
     */
    public $serviceId;
    /**
     * Departure country 2 letter code.
     *
     * @var string
     */
    public $fromCountry;
    /**
     * Departure country postal/zip code.
     *
     * @var string
     */
    public $fromZip;
    /**
     * Destination country 2 letter code.
     *
     * @var string
     */
    public $toCountry;
    /**
     * Destination country postal/zip code.
     *
     * @var string
     */
    public $toZip;
    /**
     * Package width in cm.
     *
     * @var float
     */
    public $packageWidth;
    /**
     * Package height in cm.
     *
     * @var float
     */
    public $packageHeight;
    /**
     * Package length in cm.
     *
     * @var float
     */
    public $packageLength;
    /**
     * Package weight in kg.
     *
     * @var float
     */
    public $packageWeight;

    /**
     * Transforms DTO to its array format suitable for http client.
     *
     * @return array DTO in array format.
     */
    public function toArray()
    {
        return array(
            'service_id' => $this->serviceId,
            'from[country]' => $this->fromCountry,
            'from[zip]' => $this->fromZip,
            'to[country]' => $this->toCountry,
            'to[zip]' => $this->toZip,
            'packages[0][height]' => $this->packageHeight,
            'packages[0][width]' => $this->packageWidth,
            'packages[0][length]' => $this->packageLength,
            'packages[0][weight]' => $this->packageWeight,
            'source' => 'PRO',
        );
    }

    /**
     * Transforms raw array data to object.
     *
     * @param array $raw Raw array data.
     *
     * @return static Transformed DTO object.
     */
    public static function fromArray(array $raw)
    {
        $instance = new static();
        $instance->serviceId = self::getValue($raw, 'service_id');
        $instance->fromCountry = self::getValue($raw, 'from[country]');
        $instance->fromZip = self::getValue($raw, 'from[zip]');
        $instance->toCountry = self::getValue($raw, 'to[country]');
        $instance->toZip = self::getValue($raw, 'to[zip]');
        $instance->packageHeight = self::getValue($raw, 'packages[0][height]');
        $instance->packageWidth = self::getValue($raw, 'packages[0][width]');
        $instance->packageLength = self::getValue($raw, 'packages[0][length]');
        $instance->packageWeight = self::getValue($raw, 'packages[0][weight]');

        return $instance;
    }
}
