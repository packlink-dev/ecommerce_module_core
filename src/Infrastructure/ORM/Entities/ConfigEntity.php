<?php

namespace Logeecom\Infrastructure\ORM\Entities;

use Logeecom\Infrastructure\ORM\Configuration\EntityConfiguration;
use Logeecom\Infrastructure\ORM\Configuration\Indexes\StringIndex;
use Logeecom\Infrastructure\ORM\Configuration\IndexMap;

/**
 * Class Configuration
 * @package Logeecom\Infrastructure\ORM\Entities
 */
class ConfigEntity extends Entity
{
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
        $map->addIndex(new StringIndex('name', 1));
        $map->addIndex(new StringIndex('systemId', 2));

        return new EntityConfiguration($map, 'Configuration', 'Configuration');
    }
}
