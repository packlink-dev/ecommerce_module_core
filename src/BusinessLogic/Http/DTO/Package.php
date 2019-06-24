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
     * Package constructor.
     *
     * @param float $weight Weight of package in kg.
     * @param float $width Width of package in cm.
     * @param float $height Height of package in cm.
     * @param float $length Length of package in cm.
     */
    public function __construct($weight = 0.0, $width = 0.0, $height = 0.0, $length = 0.0)
    {
        $this->weight = $weight;
        $this->width = $width;
        $this->height = $height;
        $this->length = $length;
    }

    /**
     * Gets default package details.
     *
     * @return static Default package.
     */
    public static function defaultPackage()
    {
        return new static(1, 10, 10, 10);
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
        $instance->weight = static::getValue($raw, 'weight', 0.0);
        $instance->length = static::getValue($raw, 'length', 0.0);
        $instance->height = static::getValue($raw, 'height', 0.0);
        $instance->width = static::getValue($raw, 'width', 0.0);

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
