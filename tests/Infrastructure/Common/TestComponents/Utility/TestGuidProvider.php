<?php

namespace Logeecom\Tests\Infrastructure\Common\TestComponents\Utility;

use Logeecom\Infrastructure\Utility\GuidProvider;

class TestGuidProvider extends GuidProvider
{
    private $guid = '';

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
            static::$instance = new self();
        }

        return static::$instance;
    }

    public function generateGuid()
    {
        if (empty($this->guid)) {
            return parent::generateGuid();
        }

        return $this->guid;
    }

    /**
     * @param string $guid
     */
    public function setGuid($guid)
    {
        $this->guid = $guid;
    }
}