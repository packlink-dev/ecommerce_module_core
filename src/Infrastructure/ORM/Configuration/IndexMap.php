<?php

namespace Logeecom\Infrastructure\ORM\Configuration;

use Logeecom\Infrastructure\ORM\Configuration\Indexes\Index;

/**
 * Class IndexMap
 * @package Logeecom\Infrastructure\ORM\Configuration
 */
class IndexMap
{
    /**
     * Index config
     *
     * @var Index[]
     */
    private $indexes = array();

    /**
     * Adds index to map
     *
     * @param Index $index Index to be added
     *
     * @return IndexMap
     */
    public function addIndex(Index $index)
    {
        $this->indexes[$index->getProperty()] = $index;

        return $this;
    }

    /**
     * Returns array of indexes
     *
     * @return Index[] Array of indexes
     */
    public function getIndexes()
    {
        return $this->indexes;
    }
}
