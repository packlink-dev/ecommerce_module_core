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
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoFactoryRegistrationException
     */
    protected function setUp()
    {
        parent::setUp();

        TestFrontDtoFactory::register(ValidationError::CLASS_KEY, ValidationError::CLASS_NAME);
    }

    protected function tearDown()
    {
        parent::tearDown();

        TestFrontDtoFactory::reset();
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testFromArrayNotImplemented()
    {
        Address::fromArray(array());
    }
}
