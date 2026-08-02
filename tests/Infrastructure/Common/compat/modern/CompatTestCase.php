<?php

namespace Logeecom\Tests\Infrastructure\Common;

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit >=10 compatible base: fixture methods require a void return type there,
 * which is a parse error on PHP 7.0 -- kept in a separate file for that reason.
 *
 * Excluded from the classmap; only loadable through the explicit require in tests/bootstrap.php.
 *
 * @package Logeecom\Tests\Infrastructure\Common
 */
abstract class CompatTestCase extends TestCase
{
    protected function setUp(): void
    {
        if (method_exists($this, 'before')) {
            $this->before();
        }
    }

    protected function tearDown(): void
    {
        if (method_exists($this, 'after')) {
            $this->after();
        }
    }
}
