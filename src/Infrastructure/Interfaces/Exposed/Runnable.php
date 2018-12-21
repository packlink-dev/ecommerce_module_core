<?php

namespace Logeecom\Infrastructure\Interfaces\Exposed;

/**
 * Interface Runnable.
 *
 * @package Logeecom\Infrastructure\Interfaces\Exposed
 */
interface Runnable extends \Serializable
{
    /**
     * Starts runnable run logic
     */
    public function run();
}
