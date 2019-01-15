<?php

namespace Logeecom\Tests\Infrastructure\Configuration;

use Logeecom\Infrastructure\Configuration\ConfigEntity;
use PHPUnit\Framework\TestCase;

/**
 * Class ConfigEntityTest.
 *
 * @package Logeecom\Tests\Infrastructure\Configuration
 */
class ConfigEntityTest extends TestCase
{
    public function testToArray()
    {
        $entity = new ConfigEntity();
        $entity->id = 1234;
        $entity->setName('test_name');
        $entity->setValue('test_value');
        $entity->setSystemId(221);

        $this->assertProperties($entity->toArray(), $entity);
    }

    public function testFromArray()
    {
        $data = array(
            'id' => 1234,
            'name' => 'test_name',
            'value' => 'test_value',
            'systemId' => 221,
        );

        $this->assertProperties($data, ConfigEntity::fromArray($data));
    }

    private function assertProperties($expected, ConfigEntity $entity)
    {
        self::assertEquals($expected['id'], $entity->getId());
        self::assertEquals($expected['name'], $entity->getName());
        self::assertEquals($expected['value'], $entity->getValue());
        self::assertEquals($expected['systemId'], $entity->getSystemId());
    }
}
