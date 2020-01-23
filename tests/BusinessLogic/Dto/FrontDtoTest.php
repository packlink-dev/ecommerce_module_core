<?php

namespace Logeecom\Tests\BusinessLogic\Dto;

use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\EmptyFrontDto;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\FooDto;
use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException;

/**
 * Class FrontDtoTest.
 *
 * @package Logeecom\Tests\BusinessLogic\Dto
 */
class FrontDtoTest extends BaseDtoTest
{
    /**
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function testFromArray()
    {
        $instance = FooDto::fromArray(array('foo' => 'foo_value', 'bar' => 'bar_value'));
        $this->assertSame('foo_value', $instance->foo, 'From array did not create instance properly.');
        $this->assertSame('bar_value', $instance->bar, 'From array did not create instance properly.');
    }

    /**
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function testToArray()
    {
        $instance = FooDto::fromArray(array('foo' => 'foo_value', 'bar' => 'bar_value'));
        $result = $instance->toArray();
        $this->assertArrayHasKey('foo', $result, 'To array did not create proper array.');
        $this->assertArrayHasKey('bar', $result, 'To array did not create proper array.');
        $this->assertSame($instance->foo, $result['foo'], 'To array did not set proper value.');
        $this->assertSame($instance->bar, $result['bar'], 'To array did not set proper value.');
    }

    /**
     * @expectedException \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function testFromArrayFieldsNotImplemented()
    {
        EmptyFrontDto::fromArray(array('field'));
    }

    /**
     * @expectedException \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function testFromArrayWrongFields()
    {
        FooDto::fromArray(array('foo' => 'something', 'bad_field' => 'value'));
    }

    public function testValidation()
    {
        /** @var \Packlink\BusinessLogic\DTO\ValidationError[] $errors */
        $errors = array();
        try {
            FooDto::fromArray(array('whatever' => 123, 'again' => 'bad'));
        } catch (FrontDtoValidationException $exception) {
            $errors = $exception->getValidationErrors();
        }

        $this->assertCount(2, $errors, 'All missing fields should be added to validation errors.');
        $this->assertEquals('foo', $errors[0]->field);
        $this->assertEquals('bar', $errors[1]->field);
    }
}
