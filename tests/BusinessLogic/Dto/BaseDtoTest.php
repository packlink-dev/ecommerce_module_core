<?php

namespace Logeecom\Tests\BusinessLogic\Dto;

use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\TestFrontDtoFactory;
use Packlink\BusinessLogic\DTO\ValidationError;
use Packlink\BusinessLogic\Http\DTO\Draft\Address;
use Logeecom\Tests\Infrastructure\Common\CompatTestCase as TestCase;

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
    protected function before()
    {

        TestFrontDtoFactory::register(ValidationError::CLASS_KEY, ValidationError::CLASS_NAME);
    }

    /**
     * @return void
     */
    protected function after()
    {

        TestFrontDtoFactory::reset();
    }

    /**
     * @return void
     */
    public function testFromArrayNotImplemented()
    {
        $exThrown = null;
        try {
            Address::fromArray(array());
        } catch (\RuntimeException $ex) {
            $exThrown = $ex;
        }

        $this->assertNotNull($exThrown);
    }
}
