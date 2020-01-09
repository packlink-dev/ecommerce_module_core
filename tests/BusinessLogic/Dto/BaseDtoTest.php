<?php

namespace Logeecom\Tests\BusinessLogic\Dto;

use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\TestFrontDtoFactory;
use Packlink\BusinessLogic\DTO\ValidationError;
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
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\DtoFactoryRegistrationException
     */
    protected function setUp()
    {
        parent::setUp();

        TestFrontDtoFactory::register('validation_error', ValidationError::CLASS_NAME);
    }

    protected function tearDown()
    {
        parent::tearDown();

        TestFrontDtoFactory::reset();
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testFromArrayNotImplemented()
    {
        Address::fromArray(array());
    }
}
