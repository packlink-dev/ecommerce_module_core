<?php

namespace Packlink\BusinessLogic\Http\DTO;

/**
 * Class Package.
 *
 * @package Packlink\BusinessLogic\Http\DTO
 */
class Package extends BaseDto
{
    /**
     * Weight of package in kg.
     *
     * @var float
     */
    public $weight;
    /**
     * Width of package in cm.
     *
     * @var float
     */
    public $width;
    /**
     * Height of package in cm.
     *
     * @var float
     */
    public $height;
    /**
     * Length of package in cm.
     *
     * @var float
     */
    public $length;

    /**
     * Gets default package details.
     *
     * @return static Default package.
     */
    public static function defaultPackage()
    {
        return static::fromArray(
            array(
                'weight' => 1,
                'width' => 10,
                'height' => 10,
                'length' => 10,
            )
        );
    }

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
        $instance->weight = static::getValue($raw, 'weight', null);
        $instance->length = static::getValue($raw, 'length', null);
        $instance->height = static::getValue($raw, 'height', null);
        $instance->width = static::getValue($raw, 'width', null);

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
            'width' => (int)ceil($this->width),
            'height' => (int)ceil($this->height),
            'length' => (int)ceil($this->length),
            'weight' => round($this->weight, 2),
        );
    }
}
