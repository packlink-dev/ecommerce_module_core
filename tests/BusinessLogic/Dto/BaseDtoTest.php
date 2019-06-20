<?php

namespace Logeecom\Tests\BusinessLogic\Dto;

use Packlink\BusinessLogic\Http\DTO\Draft\Address;
use PHPUnit\Framework\TestCase;

/**
 * Class BaseDtoTest.
 *
 * @package Logeecom\Tests\BusinessLogic\Dto
 */
class BaseDtoTest extends TestCase
{
    /**
     * @expectedException \BadMethodCallException
     */
    public function testFromArrayNotImplemented()
    {
        Address::fromArray(array());
    }
}
