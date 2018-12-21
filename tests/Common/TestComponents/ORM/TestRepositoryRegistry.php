<?php

namespace Logeecom\Tests\Common\TestComponents\ORM;

use Logeecom\Infrastructure\ORM\RepositoryRegistry;

class TestRepositoryRegistry extends RepositoryRegistry
{
    public static function cleanUp()
    {
        static::$repositories = array();
        static::$instantiated = array();
    }
}