<?php

namespace Logeecom\Infrastructure\ORM\Configuration;

use Logeecom\Infrastructure\ORM\Configuration\Indexes\Index;

/**
 * Represents a map of all columns that are indexed.
 *
 * @package Logeecom\Infrastructure\ORM\Configuration
 */
class IndexMap
{
    /**
     * Array of indexed columns.
     *
     * @var Index[]
     */
    private $indexes = array();

    /**
     * Adds index to map.
     *
     * @param Index $index Index to be added.
     *
     * @return self This instance for chaining.
     */
    public function addIndex(Index $index)
    {
        $this->indexes[$index->getProperty()] = $index;

        return $this;
    }

    /**
     * Returns array of indexes.
     *
     * @return Index[] Array of indexes.
     */
    public function getIndexes()
    {
        return $this->indexes;
    }
}
