<?php

namespace Packlink\BusinessLogic\Http\DTO;

/**
 * Class Draft
 * @package Packlink\BusinessLogic\Http\DTO
 */
class Draft extends BaseDto
{
    /**
     * Unique user identifier.
     *
     * @var string
     */
    public $userId;
    /**
     * Unique client identifier.
     *
     * @var string
     */
    public $clientId;
    /**
     * Unique platform identifier.
     *
     * @var string
     */
    public $platform = 'pro';
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
