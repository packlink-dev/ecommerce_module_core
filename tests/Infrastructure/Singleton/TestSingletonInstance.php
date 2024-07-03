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
     * @return void
     */
    public function testIncorrectInstance()
    {
        $exThrown = null;
        try {
            TestAutoTestLogger::getInstance();
        } catch (\RuntimeException $ex) {
            $exThrown = $ex;
        }

        $this->assertNotNull($exThrown);
    }
}
