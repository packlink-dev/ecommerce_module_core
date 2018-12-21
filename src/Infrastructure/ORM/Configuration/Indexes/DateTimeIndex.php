<?php

namespace Logeecom\Infrastructure\ORM\Configuration\Indexes;

/**
 * Class DateTimeIndex
 * @package Logeecom\Infrastructure\ORM\Configuration\Types
 */
class DateTimeIndex extends Index
{
    /**
     * Returns index field type
     *
     * @return string Field type
     */
    public function getType()
    {
        return 'dateTime';
    }
}
