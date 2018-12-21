<?php

namespace Logeecom\Infrastructure\ORM\Configuration\Indexes;

/**
 * Class StringIndex
 * @package Logeecom\Infrastructure\ORM\Configuration\Types
 */
class StringIndex extends Index
{
    /**
     * Returns index field type
     *
     * @return string Field type
     */
    public function getType()
    {
        return 'string';
    }
}
