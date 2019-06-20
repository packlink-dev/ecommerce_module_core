<?php

namespace Packlink\BusinessLogic\Http\DTO;

/**
 * Class ParcelInfo.
 *
 * @package Packlink\BusinessLogic\Http\DTO
 */
class ParcelInfo extends BaseDto
{
    /**
     * Id of the parcel.
     *
     * @var string
     */
    public $id;
    /**
     * Name of the parcel.
     *
     * @var string
     */
    public $name;
    /**
     * Weight of the parcel.
     *
     * @var float
     */
    public $weight;
    /**
     * Length of the parcel.
     *
     * @var float
     */
    public $length;
    /**
     * Height of the parcel.
     *
     * @var float
     */
    public $height;
    /**
     * Width of the parcel.
     *
     * @var float
     */
    public $width;
    /**
     * Created date of the parcel.
     *
     * @var \DateTime
     */
    public $createdAt;
    /**
     * Updated date of the parcel.
     *
     * @var \DateTime
     */
    public $updatedAt;
    /**
     * Represent if it's the default parcel.
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
        $instance->name = static::getValue($raw, 'name');
        $instance->weight = static::getValue($raw, 'weight');
        $instance->length = static::getValue($raw, 'length');
        $instance->height = static::getValue($raw, 'height');
        $instance->width = static::getValue($raw, 'width');
        $instance->default = static::getValue($raw, 'default');

        $instance->updatedAt = static::getValue($raw, 'updated_at');
        $instance->updatedAt = $instance->updatedAt ? \DateTime::createFromFormat('Y-m-d H:i:s', $instance->updatedAt)
            : null;

        $instance->createdAt = static::getValue($raw, 'created_at');
        $instance->createdAt = $instance->createdAt ? \DateTime::createFromFormat('Y-m-d H:i:s', $instance->createdAt)
            : null;

        return $instance;
    }

    /**
     * Gets default parcel details.
     *
     * @return static Default parcel.
     */
    public static function defaultParcel()
    {
        return static::fromArray(
            array(
                'name' => 'Default parcel',
                'weight' => 1,
                'width' => 10,
                'height' => 10,
                'length' => 10,
                'default' => true,
            )
        );
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
            'name' => $this->name,
            'weight' => $this->weight,
            'length' => $this->length,
            'height' => $this->height,
            'width' => $this->width,
            'updated_at' => $this->updatedAt ? $this->updatedAt->format('Y-m-d H:i:s') : null,
            'created_at' => $this->createdAt ? $this->createdAt->format('Y-m-d H:i:s') : null,
            'default' => $this->default,
        );
    }
}
