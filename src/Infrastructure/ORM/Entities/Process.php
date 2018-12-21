<?php

namespace Logeecom\Infrastructure\ORM\Entities;

use Logeecom\Infrastructure\ORM\Configuration\EntityConfiguration;
use Logeecom\Infrastructure\ORM\Configuration\Indexes\StringIndex;
use Logeecom\Infrastructure\ORM\Configuration\IndexMap;

/**
 * Class Process
 * @package Logeecom\Infrastructure\ORM\Entities
 */
class Process extends Entity
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * @var string
     */
    public $guid;

    /**
     * @var string
     */
    public $runner;

    /**
     * Returns entity configuration object
     *
     * @return EntityConfiguration
     */
    public function getConfig()
    {
        $indexMap = new IndexMap();
        $indexMap->addIndex(new StringIndex('guid', 1));

        return new EntityConfiguration($indexMap, 'Process', 'Process');
    }
}
