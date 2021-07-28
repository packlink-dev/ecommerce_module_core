<?php

namespace Logeecom\Tests\Infrastructure\Singleton;

use Logeecom\Infrastructure\AutoTest\AutoTestLogger;

/**
 * Class TestAutoTestLogger.
 *
 * @package Logeecom\Tests\Infrastructure\Singleton
 */
class TestAutoTestLogger extends AutoTestLogger
{
    /**
     * Creates instance of this class.
     *
     * @return static
     *
     * @noinspection PhpDocSignatureInspection
     */
    public static function create()
    {
        return new self();
    }
}