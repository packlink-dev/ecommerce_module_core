<?php

namespace Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\Entity;

use Logeecom\Infrastructure\ORM\Configuration\EntityConfiguration;
use Logeecom\Infrastructure\ORM\Configuration\IndexMap;
use Logeecom\Infrastructure\ORM\Entity;

/**
 * Class FooEntity.
 *
 * @package Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\Entity
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
     * Array of field names.
     *
     * @var array
     */
    protected $fields = array(
        'id',
        'text',
        'int',
        'intNegative',
        'date',
        'boolTrue',
        'boolFalse',
        'double',
        'doubleNegative',
        'empty',
    );

    /**
     * Returns entity configuration object
     *
     * @return EntityConfiguration
     */
    public function getConfig()
    {
        $map = new IndexMap();
        $map->addStringIndex('text');
        $map->addIntegerIndex('int');
        $map->addIntegerIndex('intNegative');
        $map->addDateTimeIndex('date');
        $map->addBooleanIndex('boolTrue');
        $map->addBooleanIndex('boolFalse');
        $map->addDoubleIndex('double');
        $map->addDoubleIndex('doubleNegative');
        $map->addDoubleIndex('empty');

        return new EntityConfiguration($map, 'TestEntity');
    }
}
