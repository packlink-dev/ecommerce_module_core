<?php

namespace Logeecom\Tests\Infrastructure\Common;

use PHPUnit\Framework\TestCase;

/**
 * PHP 7.0 / PHPUnit <10 compatible base: no return type declarations allowed here.
 *
 * Excluded from the classmap; only loadable through the explicit require in tests/bootstrap.php.
 *
 * @package Logeecom\Tests\Infrastructure\Common
 */
abstract class CompatTestCase extends TestCase
{
    protected function setUp()
    {
        if (method_exists($this, 'before')) {
            $this->before();
        }
    }

    protected function tearDown()
    {
        if (method_exists($this, 'after')) {
            $this->after();
        }
    }
}
