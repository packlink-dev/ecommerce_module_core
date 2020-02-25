<?php

namespace Logeecom\Tests\Infrastructure\Singleton;

use Logeecom\Infrastructure\AutoTest\AutoTestLogger;
use PHPUnit\Framework\TestCase;

/**
 * Class TestSingletonInstance.
 *
 * @package Logeecom\Tests\Infrastructure\Singleton
 */
class TestSingletonInstance extends TestCase
{
    public function testCorrectInstance()
    {
        $instance = AutoTestLogger::getInstance();

        $this->assertNotNull($instance);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testIncorrectInstance()
    {
        TestAutoTestLogger::getInstance();
    }
}
