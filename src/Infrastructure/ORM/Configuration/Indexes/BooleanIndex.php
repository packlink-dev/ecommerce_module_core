<?php

namespace Logeecom\Infrastructure\ORM\Configuration\Indexes;

/**
 * Class BooleanIndex
 * @package Logeecom\Infrastructure\ORM\Configuration\Indexes
 */
class BooleanIndex extends Index
{
    /**
     * Returns index field type
     *
     * @return string Field type
     */
    public function getType()
    {
        return 'boolean';
    }
}
