<?php

namespace Logeecom\Infrastructure\ORM\Configuration\Indexes;

/**
 * Class DoubleIndex
 * @package Logeecom\Infrastructure\ORM\Configuration\Types
 */
class DoubleIndex extends Index
{
    /**
     * Returns index field type
     *
     * @return string Field type
     */
    public function getType()
    {
        return 'double';
    }
}
