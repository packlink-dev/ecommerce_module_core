<?php

namespace Logeecom\Infrastructure\Utility;

/**
 * Class GuidProvider.
 *
 * @package Logeecom\Infrastructure\Utility
 */
class GuidProvider
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * Singleton instance of this class.
     *
     * @var GuidProvider
     */
    protected static $instance;

    /**
     * GuidProvider constructor.
     */
    private function __construct()
    {
    }

    /**
     * Returns singleton instance of GuidProvider.
     *
     * @return GuidProvider Instance of GuidProvider class.
     */
    public static function getInstance()
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * @return string
     *
     * @throws \RuntimeException
     */
    public function generateGuid()
    {
        try {
            return bin2hex(random_bytes(32));
        } catch (\Exception $e) {
            throw new \RuntimeException('Unable to generate GUID.', 0, $e);
        }
    }
}
