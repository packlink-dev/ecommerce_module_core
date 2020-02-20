<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace Logeecom\Tests\BusinessLogic\Dto;

use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\FooDto;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\NonFrontDto;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\TestFrontDtoFactory;

/**
 * Class FrontDtoFactoryTest.
 *
 * @package Logeecom\Tests\BusinessLogic\Dto
 */
class FrontDtoFactoryTest extends BaseDtoTest
{
    public function testRegister()
    {
        TestFrontDtoFactory::register(FooDto::CLASS_KEY, FooDto::CLASS_NAME);
        $instance = TestFrontDtoFactory::get(FooDto::CLASS_KEY, array('foo' => 'foo_value', 'bar' => 'bar_value'));

        $this->assertNotNull($instance, 'Instance should be created.');
        $this->assertSame('foo_value', $instance->foo, 'Front DTO Factory did not create instance properly.');
        $this->assertSame('bar_value', $instance->bar, 'Front DTO Factory did not create instance properly.');
    }

    public function testGetBatch()
    {
        TestFrontDtoFactory::register(FooDto::CLASS_KEY, FooDto::CLASS_NAME);
        $instances = TestFrontDtoFactory::getFromBatch(
            FooDto::CLASS_KEY,
            array(
                array('foo' => 'foo_value', 'bar' => 'bar_value'),
                array('foo' => 'foo_value2', 'bar' => 'bar_value2'),
            )
        );

        $this->assertCount(2, $instances, '2 instances should be created.');
        $this->assertSame('foo_value', $instances[0]->foo, 'Front DTO Factory did not create instance properly.');
        $this->assertSame('bar_value', $instances[0]->bar, 'Front DTO Factory did not create instance properly.');
        $this->assertSame('foo_value2', $instances[1]->foo, 'Front DTO Factory did not create instance properly.');
        $this->assertSame('bar_value2', $instances[1]->bar, 'Front DTO Factory did not create instance properly.');
    }

    /**
     * @expectedException \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
     */
    public function testNotRegistered()
    {
        TestFrontDtoFactory::get(FooDto::CLASS_KEY, array());
    }

    /**
     * @expectedException \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
     */
    public function testNotRegisteredBatch()
    {
        TestFrontDtoFactory::getFromBatch(FooDto::CLASS_KEY, array());
    }

    /**
     * @expectedException \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoFactoryRegistrationException
     */
    public function testBadClassRegistration()
    {
        TestFrontDtoFactory::register(FooDto::CLASS_KEY, NonFrontDto::CLASS_NAME);
    }

    /**
     * @expectedException \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function testGetWrongPayload()
    {
        TestFrontDtoFactory::register(FooDto::CLASS_KEY, FooDto::CLASS_NAME);
        TestFrontDtoFactory::get(FooDto::CLASS_KEY, array('foo' => 'something', 'whatever' => 123));
    }
}
