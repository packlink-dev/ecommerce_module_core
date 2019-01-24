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
     * Array of parcels.
     *
     * @var ParcelInfo[]
     */
    public $parcels;

    /**
     * Transforms DTO to its array format suitable for http client.
     *
     * @return array DTO in array format.
     */
    public function toArray()
    {
        $data = array(
            'service_id' => $this->serviceId,
            'from[country]' => $this->fromCountry,
            'from[zip]' => $this->fromZip,
            'to[country]' => $this->toCountry,
            'to[zip]' => $this->toZip,
            'source' => 'PRO',
        );

        foreach ($this->parcels as $index => $parcel) {
            $data["packages[$index][height]"] = $parcel->height;
            $data["packages[$index][width]"] = $parcel->width;
            $data["packages[$index][length]"] = $parcel->length;
            $data["packages[$index][weight]"] = $parcel->weight;
        }

        return $data;
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
        $instance->parcels = array();

        $index = 0;
        while (array_key_exists("packages[$index][height]", $raw)) {
            $parcel = new ParcelInfo();

            $parcel->height = self::getValue($raw, "packages[$index][height]");
            $parcel->width = self::getValue($raw, "packages[$index][width]");
            $parcel->length = self::getValue($raw, "packages[$index][length]");
            $parcel->weight = self::getValue($raw, "packages[$index][weight]");

            $instance->parcels[] = $parcel;
            $index++;
        }

        return $instance;
    }
}
