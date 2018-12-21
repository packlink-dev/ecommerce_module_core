<?php

namespace Logeecom\Tests\Infrastructure\ORM;

use Logeecom\Infrastructure\ORM\Configuration\EntityConfiguration;
use Logeecom\Infrastructure\ORM\Configuration\IndexMap;
use PHPUnit\Framework\TestCase;

/**
 * Class EntityConfigurationTest
 * @package Logeecom\Tests\Infrastructure\ORM
 */
class EntityConfigurationTest extends TestCase
{

    public function testEntityConfiguration()
    {
        $map = new IndexMap();
        $type = 'test';
        $code = 'test';
        $config = new EntityConfiguration($map, $type, $code);

        $this->assertEquals($map, $config->getIndexMap());
        $this->assertEquals($type, $config->getType());
        $this->assertEquals($code, $config->getCode());
    }
}
