<?php

namespace Logeecom\Tests\Common\TestComponents\ORM;

use Logeecom\Infrastructure\ORM\Entities\Entity;

/**
 * Class MemoryStorage
 * @package Logeecom\Tests\Common\TestComponents\ORM\Entity
 */
class MemoryStorage
{
    private static $incrementId = 1;

    /**
     * @var Entity[]
     */
    public static $storage = array();

    /**
     * @return int
     */
    public static function generateId()
    {
        return static::$incrementId++;
    }

    /**
     * Empties storage.
     */
    public static function reset()
    {
        static::$storage = array();
    }
}
