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
     * 2 letter country code.
     *
     * @var string
     */
    public $fromCountry;
    /**
     * Postal code.
     *
     * @var string
     */
    public $fromZip;
    /**
     * 2 letter country code.
     *
     * @var string
     */
    public $toCountry;
    /**
     * Postal code.
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
     * @inheritdoc
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
     * @inheritdoc
     */
    public static function fromArray(array $raw)
    {
        // this class is not intent to be built from array
        return new static();
    }
}
