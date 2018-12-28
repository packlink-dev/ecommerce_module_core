<?php

namespace Packlink\BusinessLogic\Http\DTO\Draft;

use Packlink\BusinessLogic\Http\DTO\BaseDto;

/**
 * Class Package
 * @package Packlink\BusinessLogic\Http\DTO\Draft
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
     * Transforms DTO to its array format suitable for http client.
     *
     * @return array DTO in array format.
     */
    public function toArray()
    {
        return array(
            'width' => round($this->width, 2),
            'height' => round($this->height, 2),
            'length' => round($this->length, 2),
            'weight' => round($this->weight, 2),
        );
    }
}
