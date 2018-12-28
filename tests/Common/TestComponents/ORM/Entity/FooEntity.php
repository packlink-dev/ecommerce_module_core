<?php

namespace Logeecom\Tests\Common\TestComponents\ORM\Entity;

use Logeecom\Infrastructure\ORM\Configuration\Indexes\BooleanIndex;
use Logeecom\Infrastructure\ORM\Configuration\Indexes\DateTimeIndex;
use Logeecom\Infrastructure\ORM\Configuration\Indexes\DoubleIndex;
use Logeecom\Infrastructure\ORM\Configuration\Indexes\IntegerIndex;
use Logeecom\Infrastructure\ORM\Configuration\Indexes\StringIndex;
use Logeecom\Infrastructure\ORM\Configuration\IndexMap;
use Logeecom\Infrastructure\ORM\Entities\Entity;
use Logeecom\Infrastructure\ORM\Configuration\EntityConfiguration;

/**
 * Class TestEntity
 * @package Logeecom\Tests\Common\TestComponents\ORM\Entity
 */
class FooEntity extends Entity
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;

    public $text = 'Test';

    public $int = 123;

    public $intNegative = -234;

    public $date;

    public $boolTrue = true;

    public $boolFalse = false;

    public $double = 123.5;

    public $doubleNegative = -678.75;

    public $empty = 123;

    /**
     * Returns entity configuration object
     *
     * @return EntityConfiguration
     */
    public function getConfig()
    {
        $map = new IndexMap();
        $map->addIndex(new StringIndex('text'));
        $map->addIndex(new IntegerIndex('int'));
        $map->addIndex(new IntegerIndex('intNegative'));
        $map->addIndex(new DateTimeIndex('date'));
        $map->addIndex(new BooleanIndex('boolTrue'));
        $map->addIndex(new BooleanIndex('boolFalse'));
        $map->addIndex(new DoubleIndex('double'));
        $map->addIndex(new DoubleIndex('doubleNegative'));
        $map->addIndex(new DoubleIndex('empty'));

        return new EntityConfiguration($map, 'TestEntity');
    }
}
