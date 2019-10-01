<?php

namespace Logeecom\Tests\Infrastructure\ORM;

use Logeecom\Infrastructure\ORM\IntermediateObject;
use Logeecom\Infrastructure\Serializer\Serializer;
use PHPUnit\Framework\TestCase;

/**
 * Class IntermediateObjectTest.
 *
 * @package Logeecom\Tests\Infrastructure\ORM
 */
class IntermediateObjectTest extends TestCase
{
    public function testIndexes()
    {
        $object = new IntermediateObject();
        $object->setIndex1('test');
        $object->setIndexValue(3, 'test2');

        $this->assertEquals('test', $object->getIndexValue(1));
        $this->assertEquals('test', $object->getIndex1());

        $this->assertEquals('test2', $object->getIndexValue(3));
        $this->assertEquals('test2', $object->getIndex3());

        for ($i = 1; $i < 10; $i++) {
            $object->setIndexValue($i, 'test' . $i);
        }

        for ($i = 1; $i < 10; $i++) {
            $this->assertEquals('test' . $i, $object->getIndexValue($i));
        }
    }

    public function testIndexesWrongIndex()
    {
        $object = new IntermediateObject();
        $object->setIndexValue(-1, 'test');
        $this->assertNull($object->getIndexValue(-1));

        $object->setIndexValue('bla', 'test');
        $this->assertNull($object->getIndexValue('bla'));

        $object->setIndexValue(1, 1);
        $this->assertNull($object->getIndexValue(1));
    }

    public function testSetData()
    {
        $data = Serializer::serialize(array(1, 'a' => 5, 6));
        $object = new IntermediateObject();
        $object->setData($data);
        $this->assertEquals($data, $object->getData());
    }
}
