<?php

namespace Packlink\BusinessLogic\Http\DTO;

/**
 * Class DropOff
 * @package Packlink\BusinessLogic\Http\DTO
 */
class DropOff extends BaseDto
{
    /**
     * Unique identifier of drop-off point.
     *
     * @var string
     */
    public $id;
    /**
     * Name of the service.
     *
     * @var string
     */
    public $commerceName;
    /**
     * Indicates whether service is drop-off or pick-up.
     *
     * @var string
     */
    public $city;

    /**
     * Transforms DTO to its array format suitable for http client.
     *
     * @return array DTO in array format.
     */
    public function toArray()
    {
        return array();
    }
}
