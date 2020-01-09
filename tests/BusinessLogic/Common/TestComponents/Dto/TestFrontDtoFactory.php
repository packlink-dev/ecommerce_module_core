<?php

namespace Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto;

use Packlink\BusinessLogic\DTO\FrontDtoFactory;

/**
 * Class TestFrontDtoFactory.
 *
 * @package Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto
 */
class TestFrontDtoFactory extends FrontDtoFactory
{
    /**
     * Resets the registry.
     */
    public static function reset()
    {
        self::$registry = array();
    }
}
