<?php

namespace Logeecom\Infrastructure\ORM\Entities;

use Logeecom\Infrastructure\ORM\Configuration\EntityConfiguration;
use Logeecom\Infrastructure\ORM\Configuration\Indexes\StringIndex;
use Logeecom\Infrastructure\ORM\Configuration\IndexMap;

/**
 * Class ConfigEntity.
 *
 * @package Logeecom\Infrastructure\ORM\Entities
 */
class ConfigEntity extends Entity
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Configuration property name.
     *
     * @var string
     */
    public $name;
    /**
     * Configuration system identifier.
     *
     * @var string
     */
    public $systemId;
    /**
     * Configuration property value.
     *
     * @var mixed
     */
    public $value;

    /**
     * Returns entity configuration object.
     *
     * @return EntityConfiguration Configuration object.
     */
    public function getConfig()
    {
        $map = new IndexMap();
        $map->addIndex(new StringIndex('name'));
        $map->addIndex(new StringIndex('systemId'));

        return new EntityConfiguration($map, 'Configuration');
    }
}
