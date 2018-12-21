<?php

namespace Logeecom\Infrastructure\ORM\Configuration\Indexes;

/**
 * Class IntegerIndex
 * @package Logeecom\Infrastructure\ORM\Configuration\Types
 */
class IntegerIndex extends Index
{
    /**
     * Returns index field type
     *
     * @return string Field type
     */
    public function getType()
    {
        return 'integer';
    }
}
